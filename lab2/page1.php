<?php
    $title = "My Projects | Student Portfolio";
    $header = "My Projects";
?>
<html>
    <head>
        <title><?php echo $title; ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            nav {
                background-color: rgba(255, 255, 255, 0.95);
                padding: 15px 0;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                display: flex;
                justify-content: center;
                gap: 30px;
                margin-bottom: 40px;
                flex-wrap: wrap;
            }
            nav a {
                text-decoration: none;
                color: #667eea;
                font-weight: 600;
                font-size: 16px;
                transition: all 0.3s ease;
                padding: 8px 15px;
            }
            nav a:hover {
                color: #764ba2;
                border-bottom: 3px solid #667eea;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background-color: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            <h1><?php echo $header; ?></h1>
            <img src="3d.png" alt="My Projects">
            <p>
                As a Computer Science student, I have worked on a variety of projects that
                helped me sharpen my skills in programming, web development, and problem solving.
                These include a library management system built with PHP and MySQL, a simple
                inventory tracker using Python, and several web-based applications developed
                during my XAMPP and web development coursework. Each project taught me something
                new about software design, databases, and how to turn an idea into a working system.
            </p>
        </div>
        <style>
            h1 {
                color: #333;
                margin-bottom: 30px;
                font-size: 28px;
            }
            img {
                width: 100%;
                max-width: 500px;
                height: auto;
                border-radius: 10px;
                margin: 20px 0;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            p {
                color: #555;
                line-height: 1.8;
                font-size: 16px;
                text-align: justify;
            }
        </style>
    </body>
</html>
