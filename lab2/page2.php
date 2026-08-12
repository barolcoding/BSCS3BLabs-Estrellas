<?php
    $title = "About Me | Student Portfolio";
    $header = "About Me";
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
            <img src="EstrellasPfp.jpg" alt="About Me">
            <p>
                Hi, I'm a Computer Science student passionate about building useful and
                creative software. I enjoy learning new programming languages and frameworks,
                and I have hands-on experience with PHP, MySQL, and XAMPP through my coursework
                and personal projects. Outside of academics, I like exploring new technologies,
                collaborating with classmates on group projects, and continuously improving my
                skills as a future software developer.
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
