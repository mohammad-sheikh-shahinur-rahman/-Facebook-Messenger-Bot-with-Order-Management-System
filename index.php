<?php
/**
 * Facebook Messenger Bot - Index/Entry Point
 * Redirects to admin panel or shows documentation
 */

require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FB Messenger Bot - Order Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            padding: 50px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 18px;
        }
        
        .icon {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .btn-group-custom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 40px 0;
        }
        
        .btn-box {
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid #e0e0e0;
        }
        
        .btn-box:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
        }
        
        .btn-box i {
            font-size: 40px;
            color: #667eea;
            display: block;
            margin-bottom: 15px;
        }
        
        .btn-box h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .btn-box p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }
        
        .features {
            background: #f5f7fb;
            border-radius: 10px;
            padding: 30px;
            margin: 40px 0;
        }
        
        .features h4 {
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .features ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .features li {
            padding: 8px 0;
            color: #666;
            font-size: 15px;
        }
        
        .features li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .status {
            background: #e8f5e9;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #2e7d32;
        }
        
        .docs-link {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background: #f0f0f0;
            border-radius: 5px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .docs-link:hover {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px;
                border-radius: 10px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .btn-group-custom {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-robot"></i>
            </div>
            <h1>Facebook Messenger Bot</h1>
            <p>Complete Order Management System</p>
        </div>
        
        <div class="status">
            <i class="fas fa-check-circle"></i> 
            System is <strong>READY</strong> - Version <?php echo APP_VERSION; ?>
        </div>
        
        <div class="btn-group-custom">
            <a href="/admin/login.php" class="btn-box">
                <i class="fas fa-lock"></i>
                <h3>Admin Dashboard</h3>
                <p>Manage orders & analytics</p>
            </a>
            
            <a href="webhook.php" class="btn-box">
                <i class="fas fa-link"></i>
                <h3>Webhook Status</h3>
                <p>Facebook integration point</p>
            </a>
        </div>
        
        <div class="features">
            <h4><i class="fas fa-check-circle"></i> Core Features</h4>
            <ul>
                <li>Automated Facebook Messenger bot with AI-like responses</li>
                <li>5-step order collection system</li>
                <li>Real-time order database storage</li>
                <li>Admin dashboard with full order management</li>
                <li>Analytics with charts and reports</li>
                <li>CSV export functionality</li>
                <li>Secure login with bcrypt passwords</li>
                <li>Mobile-responsive design</li>
                <li>Production-ready security</li>
                <li>Complete documentation included</li>
            </ul>
        </div>
        
        <div>
            <h5 style="color: #333; margin-bottom: 15px;">📚 Documentation</h5>
            <div style="margin-bottom: 20px;">
                <a href="#" onclick="alert('Open README.md in your text editor'); return false;" class="docs-link">
                    <i class="fas fa-book"></i> README - Quick Start
                </a>
                <a href="#" onclick="alert('Open SETUP.md in your text editor'); return false;" class="docs-link">
                    <i class="fas fa-cogs"></i> SETUP - Installation Guide
                </a>
                <a href="#" onclick="alert('Open DEPLOYMENT.md in your text editor'); return false;" class="docs-link">
                    <i class="fas fa-rocket"></i> DEPLOYMENT - Production
                </a>
                <a href="#" onclick="alert('Open API_REFERENCE.md in your text editor'); return false;" class="docs-link">
                    <i class="fas fa-code"></i> API Reference
                </a>
                <a href="#" onclick="alert('Open CUSTOMIZATION.md in your text editor'); return false;" class="docs-link">
                    <i class="fas fa-palette"></i> Customization
                </a>
            </div>
        </div>
        
        <hr>
        
        <div style="text-align: center; color: #999; font-size: 13px;">
            <p>
                <i class="fas fa-info-circle"></i>
                Default Admin: <code>admin</code> / <code>admin123</code> 
                <br><strong>⚠️ Change this immediately after login!</strong>
            </p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <small style="color: #bbb;">
                FB Messenger Bot v<?php echo APP_VERSION; ?> | © <?php echo date('Y'); ?> | 
                Database: <strong><?php echo htmlspecialchars(DB_NAME); ?></strong>
            </small>
        </div>
    </div>
</body>
</html>
