<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];

// Fetch Profile
$profile_sql = "SELECT education_level, grade_year, section, stream, department, semester FROM users WHERE id = ?";
$p_stmt = $conn->prepare($profile_sql);
$p_stmt->bind_param("i", $student_id);
$p_stmt->execute();
$profile = $p_stmt->get_result()->fetch_assoc();

if (!$profile['education_level']) {
    header("Location: student_dashboard.php?error=Complete your profile first.");
    exit();
}

$level = $profile['education_level'];
$grade = $profile['grade_year'];
$section = $profile['section'];
$stream = $profile['stream'];
$dept = $profile['department'];
$sem = $profile['semester'];

// Fetch Targeted Exams
$sql = "SELECT e.*, u.name as teacher_name,
        (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as q_count,
        (SELECT COUNT(*) FROM results r WHERE r.exam_id = e.id AND r.student_id = ?) as taken
        FROM exams e 
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.status = 'active'
        AND LOWER(e.level) = LOWER(?)
        AND (e.grade_year IS NULL OR e.grade_year = '' OR e.grade_year = ?)
        AND (e.section IS NULL OR e.section = '' OR e.section = ?)
        AND (e.stream IS NULL OR e.stream = '' OR e.stream = ?)
        AND (e.department IS NULL OR e.department = '' OR e.department = ?)
        AND (e.semester IS NULL OR e.semester = '' OR e.semester = ?)
        HAVING q_count > 0
        ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issssss", $student_id, $level, $grade, $section, $stream, $dept, $sem);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Assessments | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8fafc; }
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 32px; padding: 40px 0; }
        .card-pro { 
            background: white; 
            border-radius: 24px; 
            border: 1px solid #e2e8f0; 
            overflow: hidden; 
            transition: 0.3s; 
            display: flex; 
            flex-direction: column; 
            height: 100%;
        }
        .card-pro:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            border-color: #2563eb; 
        }
        .tag { padding: 6px 14px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-bottom: 16px; }
        .tag-blue { background: #eff6ff; color: #2563eb; }
        .tag-success { background: #ecfdf5; color: #10b981; }
        
        .exam-info { padding: 32px; flex-grow: 1; }
        .exam-footer { padding: 24px 32px; background: #f8fafc; border-top: 1px solid #f1f5f9; }
        
        .empty-state {
            text-align: center;
            padding: 100px 40px;
            background: white;
            border-radius: 32px;
            border: 2px dashed #e2e8f0;
            margin: 40px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="student_dashboard.php" class="brand"><i class="fas fa-graduation-cap"></i> ExamSystem Pro</a>
            <div class="nav-links">
                <a href="student_dashboard.php">Dashboard</a>
                <a href="available_exams.php" class="active">Take Exam</a>
                <a href="student_results.php">My Insights</a>
                <a href="logout.php" class="btn-nav-primary">Sign Out</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <header style="margin: 60px 0 40px;">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 16px;">
                <a href="student_dashboard.php" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #e2e8f0; border-radius: 12px; color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i></a>
                <h1 style="font-size: 2.5rem; font-weight: 800; color: #0f172a;">Live Assessments</h1>
            </div>
            <p style="color: #64748b; font-size: 1.125rem;">Select an evaluation module to begin your performance analysis session.</p>
        </header>

        <div class="exam-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($exam = $result->fetch_assoc()): ?>
                    <?php $is_taken = ($exam['taken'] > 0); ?>
                    <div class="card-pro">
                        <div class="exam-info">
                            <span class="tag <?php echo $is_taken ? 'tag-success' : 'tag-blue'; ?>">
                                <i class="fas <?php echo $is_taken ? 'fa-circle-check' : 'fa-satellite-dish'; ?>" style="margin-right: 6px;"></i>
                                <?php echo $is_taken ? 'COMPLETED' : 'BROADCASTING'; ?>
                            </span>
                            <h3 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 16px; line-height: 1.2;"><?php echo htmlspecialchars((string)($exam['title'] ?? 'N/A')); ?></h3>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.9rem; color: #64748b;">
                                <div><i class="fas fa-bookmark" style="width: 20px; color: #2563eb;"></i> <?php echo htmlspecialchars((string)($exam['subject'] ?? 'General')); ?></div>
                                <div><i class="fas fa-clock" style="width: 20px; color: #2563eb;"></i> <?php echo $exam['duration']; ?> Min</div>
                                <div><i class="fas fa-list-check" style="width: 20px; color: #2563eb;"></i> <?php echo $exam['q_count']; ?> Items</div>
                                <div><i class="fas fa-award" style="width: 20px; color: #2563eb;"></i> <?php echo $exam['total_marks']; ?> Pts</div>
                            </div>
                        </div>
                        <div class="exam-footer">
                            <?php if ($is_taken): ?>
                                <button class="btn-block" style="background: #e2e8f0; color: #64748b; cursor: not-allowed;" disabled>Results Processed</button>
                            <?php else: ?>
                                <button onclick="startExam(<?php echo $exam['id']; ?>, '<?php echo addslashes($exam['title']); ?>', <?php echo $exam['duration']; ?>, <?php echo $exam['total_marks']; ?>, '<?php echo addslashes($exam['instructions']); ?>')" class="btn-block">Participate Now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1;" class="empty-state">
                    <div style="width: 100px; height: 100px; background: #f1f5f9; color: #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 3rem;">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 12px;">No Targeted Assessments Found</h3>
                    <p style="color: #64748b; max-width: 450px; margin: 0 auto;">Our intelligent routing system has not yet delivered any exams matching your current academic profile. Check back later for departmental updates.</p>
                </div>
            <?php endif; ?>
        </div>

        <footer style="margin-top: 80px; padding: 40px 0; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.85rem;">
            <div style="display: flex; justify-content: space-between;">
                <p>© <?php echo date('Y'); ?> ExamSystem Pro • Global Instance</p>
                <div style="display: flex; gap: 24px;">
                    <span><i class="fas fa-server"></i> Node: <?php echo defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : 'LOCAL'; ?></span>
                    <span><i class="fas fa-clock"></i> Sync: <?php echo date('H:i'); ?></span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        function startExam(id, title, duration, marks, instructions) {
            Swal.fire({
                title: `<div style="text-align: left; font-size: 1.5rem; font-weight: 800; color: #0f172a;">${title}</div>`,
                html: `
                    <div style="text-align: left; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 800;">Time Limit</div>
                                <div style="font-size: 1.1rem; font-weight: 800; color: #1e293b;">${duration} Minutes</div>
                            </div>
                            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 800;">Total Worth</div>
                                <div style="font-size: 1.1rem; font-weight: 800; color: #2563eb;">${marks} Points</div>
                            </div>
                        </div>
                        <h4 style="font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 12px; text-transform: uppercase;">Candidate Instructions</h4>
                        <div style="background: #fff7ed; padding: 20px; border-radius: 12px; border: 1px solid #ffedd5; color: #9a3412; font-size: 0.95rem; line-height: 1.6; max-height: 200px; overflow-y: auto;">
                            ${instructions ? instructions : 'No specific instructions provided. Follow standard academic conduct.'}
                        </div>
                        <div style="margin-top: 20px; font-size: 0.8rem; color: #64748b;">
                            <i class="fas fa-circle-info"></i> Your session will be monitored by the central academic node.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#f1f5f9',
                cancelButtonText: '<span style="color: #64748b; font-weight: 700;">Back</span>',
                confirmButtonText: 'Start Assessment Now',
                width: '600px',
                padding: '24px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'take_exam.php?id=' + id;
                }
            });
        }
    </script>
</body>
</html>
