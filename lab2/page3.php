<?php
    $title = "Student Portfolio";
    $header = "Contact Me";
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
                background: #000000;
                padding: 18px 24px;
                display: flex;
                justify-content: center;
                gap: 18px;
                
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
                background: #323842;
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
                width: 300px;
                max-width: 100%;
                border-radius: 16px;
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
                display: block; margin: 0 auto;
                
            }

            p {
                margin: 0;
                line-height: 1.8;
                font-size: 1.05rem;
                color: #4b5563;
            }

            .contact-box {
                margin-top: 20px;
                background: #ebeced;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 18px 20px;
                color: #374151;
                line-height: 1.8;
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
                <img src="contact.jpg" alt="Contact Me">
                <div>
                    <p>
                        Feel free to reach out to me if you'd like to collaborate, ask about my
                        projects, or just say hello. You can email me at
                        estrellaspopee@gmail.com, connect with me on LinkedIn, or check out my
                        code on GitHub. I'm always open to new opportunities, internships, and
                        interesting conversations about technology and software development.
                    </p>
                    <div class="contact-box">
                        <strong>Email:</strong> estrellaspopee@gmail.com<br>
                        <strong>LinkedIn:</strong> /in/karolbarol<br>
                        <strong>GitHub:</strong> github.com/barolcoding
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
