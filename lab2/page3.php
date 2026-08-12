<?php
    $title = "Contact Me | Student Portfolio";
    $header = "Contact Me";
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
            <img src="contact.jpg" alt="Contact Me">
            <p>
                Feel free to reach out to me if you'd like to collaborate, ask about my
                projects, or just say hello. You can email me at
                juandelacruz.cs@example.com, connect with me on LinkedIn, or check out my
                code on GitHub. I'm always open to new opportunities, internships, and
                interesting conversations about technology and software development.
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
