<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listify</title>
    <link rel="stylesheet" href="public/style/index.css">
    <link rel="icon" type="image/png" href="./public/assets/lua.png">
</head>
<body>
    <div class="main">
        <img src="public/assets/runner.png" alt="corredor" id="runner">
        <div class="content">
            <div class="texts">
                <h1>Listify</h1>
                <h3 id="animated-text">Transforme Seus Hábitos em Resultados!</h3>
                <p>Crie, gerencie e acompanhe seus hábitos diários com inteligência. Alcance seus objetivos com o poder da IA!"</p>
            </div>
            <a href="generate.php"><button class="btn">Começe agora!</button></a>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const h3 = document.getElementById("animated-text");
            
            setTimeout(() => {
                h3.style.opacity = "1";
                h3.style.transform = "translateX(0)";
            }, 300);
        });
    </script>
</body>
</html>