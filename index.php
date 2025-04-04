<?php
// Handle score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = filter_var($_POST['score'], FILTER_VALIDATE_INT);
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if (empty($name)) {
        $name = 'anon';
    }
    
    if ($score !== false && $score > 0) {
        file_put_contents('scores.txt', "$name: $score\n", FILE_APPEND);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flappy Ball</title>
    <style>
        body {
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #4EC0CA;
            font-family: Arial, sans-serif;
        }
        
        #gameCanvas {
            border: 2px solid black;
            background: #4EC0CA;
        }
        
        .score-box {
            margin: 20px;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        
        #gameOver {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="score-box">Score: <span id="score">0</span></div>
    <canvas id="gameCanvas" width="400" height="600"></canvas>
    <div id="gameOver">
        <h2>Game Over!</h2>
        <p>Final Score: <span id="finalScore">0</span></p>
        <form method="POST">
            <input type="text" name="name" placeholder="Your Name" >
            <input type="hidden" name="score" id="hiddenScore">
            <button type="submit">Save Score</button>
        </form>
    </div>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');

        //level change colors
        const birdColors = ['#FFD700', '#FF6347', '#4169E1', '#FF4500', '#8B4513'];
        const pipeColors = ['#2AA12A', '#8B0000', '#008080', '#800080', '#DAA520'];
        let gameLoop, birdY = 300, velocity = 0;
        let pipes = [], score = 0, isGameOver = false;

        // Bird properties
        const bird = {
            x: 50,
            y: birdY,
            size: 20,
            // color: '#FFD700', // Default color
            color: birdColors[0], // Default color
            draw() {
                ctx.fillStyle = this.color; // Use bird's color property
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        };

        // Add frame counter
        let frames = 0;
        // Add a variable to track the current color index
        let currentColorIndex = 0;

        // Modified Pipe class with dynamic color
        class Pipe {
            constructor() {
                this.x = canvas.width; // Start at right edge
                this.width = 50;
                this.gap = 200;
                this.top = Math.random() * (canvas.height - this.gap - 100) + 50;
                this.bottom = this.top + this.gap;
                this.passed = false;
                this.visible = true;
                this.color = pipeColors[currentColorIndex]; // Use the current color
            }

            draw() {
                if (this.visible) {
                    ctx.fillStyle = this.color; // Use pipe's color property
                    ctx.fillRect(this.x, 0, this.width, this.top);
                    ctx.fillRect(this.x, this.bottom, this.width, canvas.height - this.bottom);
                }
            }

            update() {
                this.x -= 2;
                if (!this.passed && this.x + this.width < bird.x) {
                    score++;
                    this.passed = true;
                }

                // Keep pipes visible until fully offscreen
                this.visible = this.x + this.width > 0;
            }

            checkCollision(bird) {
                return (
                    bird.x + bird.size > this.x &&
                    bird.x - bird.size < this.x + this.width &&
                    (bird.y - bird.size < this.top || bird.y + bird.size > this.bottom)
                );
            }
        }


        // Game functions
        function jump() {
            if (isGameOver) return;
            velocity = -5;
        }

        function createPipe() {
            if (!isGameOver && frames % 150 === 0) {
                pipes.push(new Pipe());
            }
            frames++;
        }

        function checkCollision(pipe) {
            return (bird.x + bird.size > pipe.x && 
                    bird.x - bird.size < pipe.x + pipe.width && 
                    (bird.y - bird.size < pipe.top || 
                     bird.y + bird.size > pipe.bottom));
        }

        function gameOver() {
            isGameOver = true;
            document.getElementById('gameOver').style.display = 'block';
            document.getElementById('finalScore').textContent = score;
            document.getElementById('hiddenScore').value = score;
            cancelAnimationFrame(gameLoop);
        }

        // Update the changeColors function
        function changeColors() {
            currentColorIndex = Math.floor(score / 10) % birdColors.length; // Update the current color index
            bird.color = birdColors[currentColorIndex]; // Change bird's color
            pipes.forEach(pipe => (pipe.color = pipeColors[currentColorIndex])); // Change pipes' color
        }


        // Main game loop
        function update() {
            ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas

            // Bird physics
            velocity += 0.25;
            bird.y += velocity;
            bird.draw();

            // Pipes
            const pipesToRemove = []; // Track pipes to remove
            pipes.forEach((pipe, index) => {
                pipe.draw();
                pipe.update();
                if (pipe.checkCollision(bird)) gameOver();

                // Mark pipes for removal only when fully offscreen
                if (pipe.x + pipe.width < 0) {
                    pipesToRemove.push(index);
                }
            });

            // Remove pipes after the loop
            pipesToRemove.forEach(index => pipes.splice(index, 1));

            // Boundaries
            if (bird.y + bird.size > canvas.height || bird.y - bird.size < 0) gameOver();

            // Score and pipe creation
            document.getElementById('score').textContent = score;

            // Check if score has changed
            if (score % 10 === 0 && score !== 0) {
                changeColors();
            }
            createPipe();

            if (!isGameOver) gameLoop = requestAnimationFrame(update);
        }

        // Event listeners
        document.addEventListener('keydown', e => {
            if (e.code === 'Space') jump();
        });
        canvas.addEventListener('click', jump);

        // Start game
        update();
    </script>
</body>
</html>
