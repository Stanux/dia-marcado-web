<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seu site de casamento foi publicado!</title>
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .header {
            background: linear-gradient(135deg, #f97373 0%, #b85c5c 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .header .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 24px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message {
            font-size: 16px;
            color: #555555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .site-info {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid #f97373;
        }
        
        .site-info-item {
            margin-bottom: 12px;
        }
        
        .site-info-item:last-child {
            margin-bottom: 0;
        }
        
        .site-info-label {
            font-size: 12px;
            color: #888888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        
        .site-info-value {
            font-size: 16px;
            color: #333333;
            font-weight: 500;
        }
        
        .cta-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #f97373 0%, #C27A92 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(212, 165, 116, 0.4);
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            box-shadow: 0 6px 20px rgba(212, 165, 116, 0.6);
        }
        
        .share-section {
            background-color: #faf8f5;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin-top: 30px;
        }
        
        .share-section p {
            margin: 0 0 15px 0;
            color: #666666;
            font-size: 14px;
        }
        
        .url-box {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px 15px;
            font-family: monospace;
            font-size: 14px;
            color: #f97373;
            word-break: break-all;
        }
        
        .footer {
            background-color: #333333;
            padding: 30px;
            text-align: center;
        }
        
        .footer p {
            color: #aaaaaa;
            font-size: 13px;
            margin: 0 0 10px 0;
            line-height: 1.5;
        }
        
        .footer .brand {
            color: #f97373;
            font-weight: 600;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .header {
                padding: 30px 20px;
            }
            .header h1 {
                font-size: 22px;
            }
            .content {
                padding: 30px 20px;
            }
            .cta-button {
                padding: 14px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding: 20px 10px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" class="email-container" align="center">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <div class="icon">💒</div>
                            <h1>Site Publicado!</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content">
                            <p class="greeting">Parabéns, {{ $coupleNames }}! 🎉</p>
                            
                            <p class="message">
                                Seu site de casamento foi publicado com sucesso e já está disponível para seus convidados!
                            </p>
                            
                            <div class="site-info">
                                <div class="site-info-item">
                                    <div class="site-info-label">Título do Site</div>
                                    <div class="site-info-value">{{ $siteTitle }}</div>
                                </div>
                                <div class="site-info-item">
                                    <div class="site-info-label">Data da Publicação</div>
                                    <div class="site-info-value">{{ $publishedAt }}</div>
                                </div>
                            </div>
                            
                            <div class="cta-container">
                                <a href="{{ $siteUrl }}" class="cta-button">Ver meu site</a>
                            </div>
                            
                            <div class="share-section">
                                <p>Compartilhe este link com seus convidados:</p>
                                <div class="url-box">{{ $siteUrl }}</div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p>Com carinho,</p>
                            <p class="brand">Equipe Wedding SaaS</p>
                            <p style="margin-top: 20px; font-size: 11px;">
                                Este é um email automático. Por favor, não responda.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
