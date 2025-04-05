<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Project Posted Successfully</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .container {
            padding: 20px;
            background-color: #f9f9f9;
        }

        .header {
            padding: 10px 0;
            text-align: center;
        }

        .content {
            margin: 20px 0;
        }

        .footer {
            font-size: 0.8em;
            text-align: center;
            color: #888;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Project Posted Successfully!</h1>
        </div>
        <div class="content">
            <p>Hi {{ $job->user->name }},</p>
            <p>Your project titled <strong>{{ $job->title }}</strong> has been posted successfully.</p>
            <p>You can view your project details by clicking the button below:</p>
            <p><a href="{{ route('job.show', $job->id) }}" class="btn">View Project</a></p>
        </div>
        <div class="footer">
            <p>If you have any questions, please contact support.</p>
        </div>
    </div>
</body>

</html>
