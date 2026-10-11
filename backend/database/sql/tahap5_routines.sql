-- PinjamAlat Tahap 5 routines for MySQL/MariaDB (XAMPP).
-- Run this against the pinjamalat database after its migrations have created the tables.
-- This script replaces only the named routines/triggers; it does not delete table data.

DROP TRIGGER IF EXISTS trg_peminjaman_status_log;
DROP TRIGGER IF EXISTS trg_peminjaman_status_log_insert;
DROP PROCEDURE IF EXISTS sp_setujui_peminjaman;
DROP FUNCTION IF EXISTS fn_hitung_denda_terlambat;

DELIMITER $$

CREATE FUNCTION fn_hitung_denda_terlambat(
    p_tanggal_jatuh_tempo DATE,
    p_tanggal_kembali DATE,
    p_tarif_per_hari DECIMAL(12,2)
)
RETURNS DECIMAL(12,2)
DETERMINISTIC
NO SQL
BEGIN
    DECLARE v_hari INT DEFAULT 0;
    SET v_hari = GREATEST(DATEDIFF(p_tanggal_kembali, p_tanggal_jatuh_tempo), 0);
    RETURN ROUND(v_hari * p_tarif_per_hari, 2);
END$$

CREATE TRIGGER trg_peminjaman_status_log_insert
AFTER INSERT ON peminjaman
FOR EACH ROW
BEGIN
    INSERT INTO riwayat_status_peminjaman
        (peminjaman_id, status_lama, status_baru, diubah_oleh, created_at)
    VALUES
        (NEW.id, NULL, NEW.status, COALESCE(@pinjamalat_status_actor_id, NEW.diproses_oleh), NOW());
END$$

CREATE TRIGGER trg_peminjaman_status_log
AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    IF NOT (OLD.status <=> NEW.status) THEN
        INSERT INTO riwayat_status_peminjaman
            (peminjaman_id, status_lama, status_baru, diubah_oleh, created_at)
        VALUES
            (NEW.id, OLD.status, NEW.status,
             COALESCE(@pinjamalat_status_actor_id, NEW.diproses_oleh, OLD.diproses_oleh), NOW());
    END IF;
END$$

CREATE PROCEDURE sp_setujui_peminjaman(
    IN p_peminjaman_id BIGINT UNSIGNED,
    IN p_petugas_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_ada INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_jumlah_detail INT DEFAULT 0;
    DECLARE v_jumlah_alat_unik INT DEFAULT 0;
    DECLARE v_terubah INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET @pinjamalat_status_actor_id = NULL;
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT COUNT(*) INTO v_ada
    FROM peminjaman
    WHERE id = p_peminjaman_id;

    IF v_ada = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PA:NOT_FOUND';
    END IF;

    SELECT status INTO v_status
    FROM peminjaman
    WHERE id = p_peminjaman_id
    FOR UPDATE;

    IF v_status <> 'menunggu' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PA:NOT_PENDING';
    END IF;

    SELECT COUNT(*), COUNT(DISTINCT alat_id)
      INTO v_jumlah_detail, v_jumlah_alat_unik
    FROM peminjaman_detail
    WHERE peminjaman_id = p_peminjaman_id;

    IF v_jumlah_detail = 0 OR v_jumlah_detail <> v_jumlah_alat_unik THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PA:ITEMS_INVALID';
    END IF;

    -- Pengurangan stok bersyarat dan transaksional. Bila satu alat saja gagal,
    -- row count tidak cocok dan handler akan membatalkan semua perubahan.
    UPDATE alat AS a
    INNER JOIN peminjaman_detail AS d ON d.alat_id = a.id
       SET a.stok_tersedia = a.stok_tersedia - d.jumlah,
           a.stok_dipinjam = a.stok_dipinjam + d.jumlah
     WHERE d.peminjaman_id = p_peminjaman_id
       AND a.aktif = 1
       AND a.deleted_at IS NULL
       AND a.stok_tersedia >= d.jumlah;

    SET v_terubah = ROW_COUNT();
    IF v_terubah <> v_jumlah_detail THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PA:STOCK_OR_INACTIVE';
    END IF;

    SET @pinjamalat_status_actor_id = p_petugas_id;
    UPDATE peminjaman
       SET status = 'disetujui',
           diproses_oleh = p_petugas_id,
           diproses_pada = NOW(),
           alasan_penolakan = NULL
     WHERE id = p_peminjaman_id AND status = 'menunggu';
    SET v_terubah = ROW_COUNT();
    SET @pinjamalat_status_actor_id = NULL;

    IF v_terubah <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PA:NOT_PENDING';
    END IF;

    COMMIT;
END$$

DELIMITER ;
