PinjamAlat - equipment validation fix

Files included:
- mobile/lib/models/models.dart
- mobile/lib/providers/app_state.dart
- mobile/lib/screens/app_screens.dart

Changes:
- Add `kondisi` to the Alat model, parsed from the API response.
- Add a `Kondisi alat` dropdown with allowed backend values: baik, rusak_ringan, rusak_berat.
- Send `kondisi` in both POST /api/alat and PUT /api/alat/{id} requests.
- Preserve the existing condition when editing equipment.
- Match the frontend's total-stock minimum validation to the backend minimum of 1.

Apply from PowerShell after downloading:
Expand-Archive "$HOME\Downloads\pinjamalat-equipment-fix.zip" -DestinationPath "C:\pinjamalat" -Force

Then restart Flutter (stop with q, then rerun):
cd C:\pinjamalat\mobile
flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000/api

This patch does not change backend files or the API service. It does not implement the return endpoint.
