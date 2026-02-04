<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .content {
            padding: 20px 0;
            color: #333;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3490dc;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div style="background-color: #f6f6f6; padding: 20px;">
        <div class="container">
            <div class="header">
                <h2>BudgetFlow</h2>
            </div>

            <div class="content">
                @yield('content')
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} BudgetFlow. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>
