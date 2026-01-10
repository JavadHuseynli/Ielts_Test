<!DOCTYPE html>
<html>
<head>
    <title>Test Audio Stream</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-box { border: 2px solid #333; padding: 20px; margin: 20px 0; }
        audio { width: 100%; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test Audio Streaming</h1>

    <?php
    // Get all audio files from uploads directory
    $uploads_dir = __DIR__ . '/../uploads/';
    $audio_files = glob($uploads_dir . '*.mp3');

    echo "<p>Found " . count($audio_files) . " audio files in uploads directory</p>";

    foreach ($audio_files as $file) {
        $filename = basename($file);
        $filesize = filesize($file);
        $stream_url = '../stream_audio.php?file=' . urlencode('uploads/' . $filename);

        echo "<div class='test-box'>";
        echo "<h3>$filename</h3>";
        echo "<p><strong>File Path:</strong> $file</p>";
        echo "<p><strong>File Size:</strong> " . number_format($filesize) . " bytes</p>";
        echo "<p><strong>Stream URL:</strong> <a href='$stream_url' target='_blank'>$stream_url</a></p>";

        echo "<h4>Test 1: Native Browser Audio Player</h4>";
        echo "<audio controls preload='metadata'>";
        echo "<source src='$stream_url' type='audio/mpeg'>";
        echo "Your browser does not support audio.";
        echo "</audio>";

        echo "<h4>Test 2: Custom Play Button</h4>";
        echo "<button onclick=\"playAudio('audio_$filename')\">Play</button> ";
        echo "<button onclick=\"pauseAudio('audio_$filename')\">Pause</button> ";
        echo "<button onclick=\"restartAudio('audio_$filename')\">Restart</button>";
        echo "<br><br>";
        echo "<audio id='audio_$filename' preload='metadata'>";
        echo "<source src='$stream_url' type='audio/mpeg'>";
        echo "</audio>";
        echo "<div id='status_$filename'></div>";

        echo "</div>";
    }
    ?>

    <script>
    function playAudio(id) {
        const audio = document.getElementById(id);
        const status = document.getElementById('status_' + id.replace('audio_', ''));

        if (!audio) {
            status.innerHTML = '<span class="error">Audio element not found!</span>';
            return;
        }

        console.log('Playing:', audio.src);

        audio.play()
            .then(() => {
                status.innerHTML = '<span class="success">✓ Playing successfully</span>';
                console.log('Playing successfully');
            })
            .catch(err => {
                status.innerHTML = '<span class="error">✗ Error: ' + err.message + '</span>';
                console.error('Play error:', err);
            });
    }

    function pauseAudio(id) {
        const audio = document.getElementById(id);
        if (audio) {
            audio.pause();
            const status = document.getElementById('status_' + id.replace('audio_', ''));
            status.innerHTML = '<span>Paused</span>';
        }
    }

    function restartAudio(id) {
        const audio = document.getElementById(id);
        if (audio) {
            audio.currentTime = 0;
            audio.play()
                .then(() => {
                    const status = document.getElementById('status_' + id.replace('audio_', ''));
                    status.innerHTML = '<span class="success">✓ Restarted</span>';
                })
                .catch(err => {
                    const status = document.getElementById('status_' + id.replace('audio_', ''));
                    status.innerHTML = '<span class="error">✗ Restart error: ' + err.message + '</span>';
                });
        }
    }

    // Add error listeners to all audio elements
    document.querySelectorAll('audio').forEach(audio => {
        audio.addEventListener('error', function(e) {
            console.error('Audio error:', this.src);
            console.error('Error code:', this.error ? this.error.code : 'unknown');
        });

        audio.addEventListener('loadedmetadata', function() {
            console.log('✓ Loaded metadata:', this.src);
        });
    });
    </script>
</body>
</html>
