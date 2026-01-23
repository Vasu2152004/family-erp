<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'Notification' }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, sans-serif !important;}
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f7fa;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Main Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07); overflow: hidden;">
                    <!-- Header with Gradient -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 40px 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">
                                {{ $headerIcon ?? '📋' }} {{ $headerTitle ?? 'Family ERP' }}
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <!-- Greeting -->
                            @if(isset($greeting))
                            <p style="margin: 0 0 24px 0; color: #1a202c; font-size: 18px; font-weight: 600; line-height: 1.5;">
                                {{ $greeting }}
                            </p>
                            @endif
                            
                            <!-- Main Message -->
                            <div style="background-color: #f7fafc; border-left: 4px solid #667eea; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                                @if(isset($introLines))
                                    @foreach($introLines as $line)
                                    <p style="margin: 0 0 12px 0; color: #2d3748; font-size: 16px; line-height: 1.6;">
                                        {!! preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', htmlspecialchars($line)) !!}
                                    </p>
                                    @endforeach
                                @endif
                            </div>
                            
                            <!-- Details Card -->
                            @if(isset($details))
                            <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                                @foreach($details as $label => $value)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                                    <span style="color: #718096; font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">{{ $label }}</span>
                                    <span style="color: #1a202c; font-size: 16px; font-weight: 600;">
                                        @php
                                            // Format numbers - show integers when appropriate, otherwise 2 decimal places
                                            if (is_numeric($value)) {
                                                $numValue = (float)$value;
                                                echo $numValue == (int)$numValue ? (int)$numValue : number_format($numValue, 2, '.', '');
                                            } else {
                                                echo $value;
                                            }
                                        @endphp
                                    </span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            <!-- Action Button -->
                            @if(isset($actionUrl) && isset($actionText))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 24px 0;">
                                        <a href="{{ $actionUrl }}" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;">
                                            {{ $actionText }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            <!-- Outro Lines -->
                            @if(isset($outroLines))
                            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                                @foreach($outroLines as $line)
                                @if(empty(trim($line)))
                                <p style="margin: 0 0 8px 0;"></p>
                                @else
                                <p style="margin: 0 0 12px 0; color: #4a5568; font-size: 15px; line-height: 1.6;">
                                    {!! preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $line) !!}
                                </p>
                                @endif
                                @endforeach
                            </div>
                            @endif
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f7fafc; padding: 30px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
                            @if(isset($salutation))
                            <p style="margin: 0 0 16px 0; color: #718096; font-size: 14px; line-height: 1.6;">
                                {!! $salutation !!}
                            </p>
                            @endif
                            <p style="margin: 0; color: #a0aec0; font-size: 12px; line-height: 1.5;">
                                © {{ date('Y') }} Family ERP. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>














