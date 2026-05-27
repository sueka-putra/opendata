<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Open Data Portal Account</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2a37;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #d9e2ec;border-radius:12px;overflow:hidden;">
          <tr>
            <td style="background:linear-gradient(135deg,#0f5ec9,#2d7eea);padding:18px 24px;color:#ffffff;font-size:20px;font-weight:700;">
              Open Data Portal
            </td>
          </tr>
          <tr>
            <td style="padding:24px;line-height:1.65;font-size:15px;">
              <p style="margin:0 0 14px 0;">Dear {{ $recipientName }},</p>
              <p style="margin:0 0 14px 0;">
                ASEANstats has registered you as a user of the Open Data Portal at
                <a href="https://opendata.aseanstats.org" style="color:#0f5ec9;text-decoration:none;">https://opendata.aseanstats.org</a>.
              </p>
              <p style="margin:0 0 14px 0;">
                You may log in using your registered email address and the temporary password below:
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:0;">
                <tr>
                  <td style="padding:14px 16px;background:#f8fbff;border:1px solid #cfe0f5;border-radius:10px;">
                    Temporary Password: <strong style="font-size:16px;letter-spacing:0.2px;">{{ $temporaryPassword }}</strong>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="font-size:0;line-height:0;height:16px;">&nbsp;</td></tr>
              </table>
              <p style="margin:0 0 14px 0;">
                For security reasons, you will be required to change your password after logging in.
              </p>
              <p style="margin:0 0 14px 0;">
                If you have any questions or encounter any issues, please feel free to contact us at
                <a href="mailto:stats@asean.org" style="color:#0f5ec9;text-decoration:none;">stats@asean.org</a>.
              </p>
              <p style="margin:20px 0 0 0;">Best regards,<br>ASEANstats</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
