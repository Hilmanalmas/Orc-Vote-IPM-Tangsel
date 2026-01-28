<?php
require_once 'config.php';

// Check Organization ID
if (!isset($_GET['org_id'])) {
    // Redirect to Landing Page for selection (or we could duplicate landing logic here)
    // For consistency, let's assume Landing Page is the entry point
    header("Location: landing.php");
    exit;
}

$orgId = $_GET['org_id'];
$stmt = $pdo->prepare("SELECT organization_name, id FROM admins WHERE id = ?");
$stmt->execute([$orgId]);
$orgData = $stmt->fetch();

if (!$orgData) {
    die("Organisasi tidak ditemukan. <a href='landing.php'>Kembali</a>");
}

// Fetch Candidates and Vote Counts for THIS Org
// Join candidates with vote counts
$sql = "
    SELECT c.name, c.photo, COUNT(v.id) as vote_count 
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE c.admin_id = :org_id
    GROUP BY c.id
    ORDER BY vote_count DESC, c.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['org_id' => $orgId]);
$results = $stmt->fetchAll();

// Prepare data for Chart.js
$names = array_column($results, 'name');
$votes = array_column($results, 'vote_count');
$leader = $results[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="media/Logo%20Orch-Vote.png">
    <title>Live Count - Orch-Vote</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body id="result-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-chart-bar"></i> Orch-Vote Result<span><?= htmlspecialchars($orgData['organization_name']) ?></span>
            </div>
            <nav>
                <ul>
                    <li><a href="admin/index.php">Admin Dashboard</a></li>
                    <li><a href="#" class="active">Live Count</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="text-center mb-4" style="margin-top: 2rem;">
            <h1>Hasil Perolehan Suara</h1>
            <p style="color: #6b7280;">Real-time update data suara masuk</p>
        </div>

        <?php
        $totalVotes = array_sum(array_column($results, 'vote_count'));
        ?>

        <style>
            .result-list {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                max-width: 800px;
                margin: 0 auto;
            }

            .result-item {
                background: white;
                border-radius: 16px;
                padding: 1.5rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                display: flex;
                align-items: center;
                gap: 1.5rem;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .result-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }

            .result-rank {
                font-size: 1.5rem;
                font-weight: 800;
                color: #94a3b8;
                width: 40px;
                text-align: center;
            }

            .rank-1 { color: #fbbf24; } /* Gold */
            .rank-2 { color: #9ca3af; } /* Silver */
            .rank-3 { color: #b45309; } /* Bronze */

            .result-photo {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid #f3f4f6;
            }
            
            .rank-1 .result-photo { border-color: #fbbf24; }

            .result-content {
                flex: 1;
            }

            .result-info {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                margin-bottom: 0.5rem;
            }

            .result-name {
                font-weight: 700;
                font-size: 1.25rem;
                color: var(--text-color);
            }

            .result-votes {
                font-weight: 600;
                color: var(--primary-color);
            }

            .progress-bg {
                background: #e5e7eb;
                height: 12px;
                border-radius: 999px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, var(--primary-color) 0%, #34d399 100%);
                border-radius: 999px;
                width: 0%; /* Animated */
                transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            @media (max-width: 600px) {
                .result-item {
                    padding: 1rem;
                    gap: 1rem;
                }
                .result-photo {
                    width: 60px;
                    height: 60px;
                }
                .result-rank {
                    font-size: 1.25rem;
                    width: 30px;
                }
                .result-name {
                    font-size: 1.1rem;
                }
            }
        </style>

        <div class="result-list">
            <?php 
            $rank = 1;
            foreach ($results as $r): 
                $percent = $totalVotes > 0 ? ($r['vote_count'] / $totalVotes) * 100 : 0;
            ?>
                <div class="result-item">
                    <div class="result-rank rank-<?= $rank ?>"><?= $rank ?></div>
                    <img src="<?= htmlspecialchars($r['photo']) ?>" class="result-photo" onerror="this.src='https://via.placeholder.com/150'">
                    
                    <div class="result-content">
                        <div class="result-info">
                            <div class="result-name"><?= htmlspecialchars($r['name']) ?></div>
                            <div class="result-votes">
                                <span style="font-size: 1.25rem;"><?= $r['vote_count'] ?></span> 
                                <span style="font-size: 0.875rem; color: #6b7280; font-weight: 400;">Suara (<?= number_format($percent, 1) ?>%)</span>
                            </div>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php 
            $rank++;
            endforeach; 
            ?>
        </div>

        <div class="text-center mb-4" style="margin-top: 3rem; display: flex; justify-content: center; gap: 1rem;">
            <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh Real-time</button>
            <button class="btn btn-accent" onclick="startSlideshow()"><i class="fas fa-play-circle"></i> Slideshow Mode</button>
        </div>
    </main>

<?php
        $settings = getSettings($pdo, $orgId);
        $maxVote = $settings['max_vote'];
        ?>
    </main>

    <!-- Slideshow Overlay -->
    <div id="slideshow-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #1a5d3a; z-index: 200; align-items: center; justify-content: center; flex-direction: column;">
        
        <!-- Controls -->
        <button onclick="stopSlideshow()" style="position: absolute; top: 2rem; right: 2rem; background: rgba(255,255,255,0.2); color: white; border: none; padding: 1rem; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; z-index: 202;">
            <i class="fas fa-times"></i>
        </button>

        <button onclick="prevSlide()" style="position: absolute; top: 50%; left: 2rem; transform: translateY(-50%); background: rgba(255,255,255,0.1); color: white; border: none; padding: 1rem; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; font-size: 2rem; z-index: 202;">
            <i class="fas fa-chevron-left"></i>
        </button>

        <button onclick="nextSlide()" style="position: absolute; top: 50%; right: 2rem; transform: translateY(-50%); background: rgba(255,255,255,0.1); color: white; border: none; padding: 1rem; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; font-size: 2rem; z-index: 202;">
            <i class="fas fa-chevron-right"></i>
        </button>
        
        <div id="slide-container" style="width: 90%; max-width: 1000px; text-align: center; height: 80vh; display: flex; flex-direction: column; justify-content: center;">
            <!-- Slide Content Injected by JS -->
        </div>

        <div id="slide-indicators" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
            <!-- Indicators injected by JS -->
        </div>
    </div>

    <script>
        // --- 1. Auto Refresh Logic (DISABLED) ---
        // --- 1. Auto Refresh Logic (DISABLED) ---
        console.log("Auto-refresh is disabled.");

        // --- 2. Slideshow Logic ---
        const rawResults = <?= json_encode($results) ?>;
        const totalVotes = <?= $totalVotes ?>;
        const maxVoteConfig = <?= $maxVote ?>;
        
        let slides = [];
        let slideIndex = 0;
        let slideInterval; // Auto-play interval
        const overlay = document.getElementById('slideshow-overlay');
        const container = document.getElementById('slide-container');
        const indicatorsContainer = document.getElementById('slide-indicators');

        function prepareSlides() {
            slides = [];
            
            // 1. Individual Slides for Top N (maxVote)
            // If fewer candidates than maxVote, just show all individually
            const topCount = Math.min(rawResults.length, maxVoteConfig);
            
            for (let i = 0; i < topCount; i++) {
                slides.push({
                    type: 'individual',
                    data: rawResults[i]
                });
            }

            // 2. Summary Slides (Batches of 10)
            const batchSize = 10;
            for (let i = 0; i < rawResults.length; i += batchSize) {
                const batch = rawResults.slice(i, i + batchSize);
                slides.push({
                    type: 'summary',
                    data: batch,
                    pageInfo: `Halaman ${Math.floor(i/batchSize) + 1}`
                });
            }
        }

        function startSlideshow() {
            prepareSlides();
            renderIndicators();
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            slideIndex = 0;
            showSlide(0);
            resetTimer();
        }

        function stopSlideshow() {
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto';
            clearInterval(slideInterval);
        }

        function resetTimer() {
            clearInterval(slideInterval);
            // Longer duration for Summary slides? Maybe fixed is fine.
            slideInterval = setInterval(nextSlide, 8000); // 8 seconds per slide
        }

        function nextSlide() {
            slideIndex = (slideIndex + 1) % slides.length;
            showSlide(slideIndex);
            resetTimer();
        }

        function prevSlide() {
            slideIndex = (slideIndex - 1 + slides.length) % slides.length;
            showSlide(slideIndex);
            resetTimer();
        }

        function renderIndicators() {
            indicatorsContainer.innerHTML = '';
            slides.forEach((_, i) => {
                const dot = document.createElement('div');
                dot.style.cssText = 'width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.3); transition: all 0.3s; cursor: pointer;';
                dot.onclick = () => {
                    slideIndex = i;
                    showSlide(i);
                    resetTimer();
                };
                indicatorsContainer.appendChild(dot);
            });
        }

        function updateIndicators(index) {
            Array.from(indicatorsContainer.children).forEach((dot, i) => {
                dot.style.background = i === index ? '#fbbf24' : 'rgba(255,255,255,0.3)';
                dot.style.transform = i === index ? 'scale(1.3)' : 'scale(1)';
            });
        }

        function showSlide(index) {
            updateIndicators(index);
            const slide = slides[index];

            // Animate Out
            container.style.opacity = 0;
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                if (slide.type === 'individual') {
                    renderIndividual(slide.data);
                } else {
                    renderSummary(slide.data, slide.pageInfo);
                }

                // Animate In
                container.style.transition = 'all 0.5s ease-out';
                container.style.opacity = 1;
                container.style.transform = 'translateY(0)';
            }, 300);
        }

        function renderIndividual(data) {
            const percent = totalVotes > 0 ? (data.vote_count / totalVotes * 100).toFixed(1) : 0;
            container.innerHTML = `
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                    <img src="${data.photo}" style="width: 220px; height: 220px; border-radius: 50%; border: 6px solid #fbbf24; object-fit: cover; margin-bottom: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
                    
                    <h2 style="color: white; font-size: 3.5rem; margin: 0; line-height: 1.2; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">${data.name}</h2>
                    
                    <div style="font-size: 6rem; font-weight: 800; color: #fbbf24; margin: 0.5rem 0;">${data.vote_count}</div>
                    
                    <div style="background: rgba(255,255,255,0.1); padding: 0.5rem 1.5rem; border-radius: 50px; color: white; font-size: 1.25rem;">
                        Total Suara Masuk · ${percent}%
                    </div>

                    <div style="margin-top: 2rem; font-size: 1.4rem; color: rgba(255,255,255,0.9); max-width: 800px; line-height: 1.5; font-style: italic;">
                        ${data.vision ? '"' + data.vision + '"' : ''}
                    </div>
                </div>
                <div style="font-size: 1rem; color: rgba(255,255,255,0.5); margin-top: 1rem;">Kandidat Pilihan</div>
            `;
        }

        function renderSummary(batch, pageInfo) {
            let html = `
                <h2 style="color: white; font-size: 2.5rem; margin-bottom: 2rem;">Rekapitulasi Suara <span style="font-size: 1rem; color: rgba(255,255,255,0.6); display: block; margin-top: 0.5rem;">${pageInfo}</span></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; width: 100%;">
            `;

            batch.forEach((c, idx) => {
                const percent = totalVotes > 0 ? (c.vote_count / totalVotes * 100).toFixed(1) : 0;
                html += `
                    <div style="background: rgba(255,255,255,0.9); border-radius: 12px; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 1rem;">
                        <img src="${c.photo}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb;">
                        <div style="flex: 1; text-align: left;">
                            <div style="font-weight: 700; color: #1f2937; font-size: 1.1rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 250px;">${c.name}</div>
                            <div style="background: #e5e7eb; height: 8px; border-radius: 4px; margin-top: 5px; width: 100%;">
                                <div style="width: ${percent}%; background: #1a5d3a; height: 100%; border-radius: 4px;"></div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; color: #1a5d3a; font-size: 1.25rem;">${c.vote_count}</div>
                            <div style="font-size: 0.75rem; color: #6b7280;">${percent}%</div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            container.innerHTML = html;
        }
    </script>
    <?php $basePath = ''; include 'footer.php'; ?>
</body>
</html>