PinjamAlat API validation diagnostics

This patch only changes mobile/lib/services/api_service.dart. For HTTP 422 responses, it displays Laravel's field-level errors in addition to (or instead of) the generic message. It does not change the equipment request payload, because the exact rejected field is not yet visible.

Apply from PowerShell after downloading this ZIP:
Expand-Archive "$HOME\Downloads\pinjamalat-validation-diagnostics.zip" -DestinationPath "C:\pinjamalat" -Force

Then restart Flutter and try creating the equipment record again. Copy the full validation message (field names and reasons). Use that to align the payload/rules precisely.
