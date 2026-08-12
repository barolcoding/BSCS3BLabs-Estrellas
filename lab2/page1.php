<?php
    $title = "Student Portfolio";
    $header = "My Projects";
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?></title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f3f6fb;
                color: #1f2937;
            }

            nav {
                background: #111827;
                padding: 18px 24px;
                display: flex;
                justify-content: center;
                gap: 18px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            }

            nav a {
                color: #ffffff;
                text-decoration: none;
                font-weight: 600;
                padding: 10px 16px;
                border-radius: 999px;
                transition: 0.2s ease;
            }

            nav a:hover {
                background: #374151;
            }

            .container {
                max-width: 960px;
                margin: 40px auto;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 18px;
                box-shadow: 0 14px 30px rgba(17, 24, 39, 0.08);
                padding: 30px;
            }

            .content {
                display: flex;
                align-items: center;
                gap: 28px;
                flex-wrap: wrap;
            }

            h1 {
                margin: 0 0 20px;
                font-size: 2.2rem;
                color: #111827;
            }

            img {
                width: 320px;
                max-width: 100%;
                border-radius: 16px;
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
            }

            p {
                margin: 0;
                line-height: 1.8;
                font-size: 1.05rem;
                color: #4b5563;
            }

            @media (max-width: 700px) {
                nav {
                    flex-wrap: wrap;
                }

                .container {
                    margin: 24px 16px;
                    padding: 22px 18px;
                }

                .content {
                    display: block;
                }

                img {
                    width: 100%;
                    margin-bottom: 18px;
                }
            }
        </style>
    </head>
    <body>
        <nav>
            <a href="page1.php">Projects</a>
            <a href="page2.php">About Me</a>
            <a href="page3.php">Contact</a>
        </nav>
        <div class="container">
            <h1><?php echo $header; ?></h1>
            <div class="content">
                <img src="3d.png" alt="My Projects">
                <p>
                    As a Computer Science student, I have worked on a variety of projects that
                    helped me sharpen my skills in programming, 3d modeling, web development, and problem solving.
                    These include a simple 3d model area during my 3d modeling and web development coursework. Each project taught me something
                    new about software design, databases, and how to turn an idea into a working system.
                </p>
            </div>
        </div>
    </body>
</html>
