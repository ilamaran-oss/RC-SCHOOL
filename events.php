<?php require "db.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Event Dashboard | RC School</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f4f6f9
        }

        .admin-wrap {
            max-width: 1200px;
            margin: 40px auto
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .admin-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .admin-card h2 {
            margin-bottom: 18px;
            color: #1f4e79;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-card input,
        .admin-card textarea,
        .admin-card select {
            width: 100%;
            padding: 12px;
            margin-bottom: 14px;
            border: 1px solid #ccd3db;
            border-radius: 8px;
            font-size: 14px;
        }

        .admin-card button {
            background: #1f4e79;
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .admin-card button:hover {
            background: #163a5a
        }

        .event-row {
            border: 1px solid #e5e8ec;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
            background: #fafbfd;
        }

        .event-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.pub {
            background: #d4edda;
            color: #155724
        }

        .badge.draft {
            background: #fff3cd;
            color: #856404
        }

        .row-actions a {
            text-decoration: none;
            font-size: 13px;
            margin-left: 10px;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .edit-btn {
            background: #e3f2fd;
            color: #0d47a1
        }

        .del-btn {
            background: #fdecea;
            color: #b71c1c
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-box h3 {
            font-size: 28px;
            color: #1f4e79
        }

        @media(max-width:900px) {
            .admin-grid {
                grid-template-columns: 1fr
            }

            .stats {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header class="main-header">
        <div class="logo">
            <span class="logo-text">RC MIDDLE SCHOOL — ADMIN</span>
        </div>
        <nav>
            <ul>
                <li><a href="home.html" class="nav-link">Home</a></li>
                <li><a href="events.php" class="nav-link">Events</a></li>
                <li><a href="admin_events.php" class="nav-link active">Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <section class="page-banner">
        <h1>Event Administration Dashboard</h1>
    </section>

    <div class="admin-wrap">

        <?php
        $total = $conn->query("SELECT COUNT(*) c FROM events")->fetch_assoc()['c'];
        $pub = $conn->query("SELECT COUNT(*) c FROM events WHERE status='published'")->fetch_assoc()['c'];
        $draft = $conn->query("SELECT COUNT(*) c FROM events WHERE status='draft'")->fetch_assoc()['c'];
        ?>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-box">
                <p>Total Events</p>
                <h3><?php echo $total; ?></h3>
            </div>
            <div class="stat-box">
                <p>Published</p>
                <h3><?php echo $pub; ?></h3>
            </div>
            <div class="stat-box">
                <p>Drafts</p>
                <h3><?php echo $draft; ?></h3>
            </div>
        </div>

        <div class="admin-grid">

            <!-- CREATE EVENT -->
            <div class="admin-card">
                <h2><span class="material-symbols-outlined">add_circle</span>Create Event</h2>

                <form action="newevent.php" method="post" enctype="multipart/form-data">
                    <input name="title" placeholder="Event Title" required>
                    <input type="date" name="event_date" required>
                    <input type="time" name="event_time">
                    <input name="location" placeholder="Location">

                    <select name="type">
                        <option>Academic</option>
                        <option>Sports</option>
                        <option>Cultural</option>
                        <option>Meeting</option>
                        <option>Other</option>
                    </select>

                    <textarea name="description" rows="5" placeholder="Event Description" required></textarea>

                    <input type="file" name="event_image" accept="image/*">

                    <select name="status">
                        <option value="published">Publish</option>
                        <option value="draft">Draft</option>
                    </select>

                    <button>Create Event</button>
                </form>
            </div>

            <!-- EVENT LIST -->
            <div class="admin-card">
                <h2><span class="material-symbols-outlined">event</span>Manage Events</h2>

                <?php
                $r = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
                while ($e = $r->fetch_assoc()) {
                    $statusClass = $e['status'] == 'published' ? 'pub' : 'draft';

                    echo "
<div class='event-row'>
  <div class='event-top'>
    <div>
      <b>{$e['title']}</b><br>
      <small>{$e['event_date']} {$e['event_time']} — {$e['location']}</small>
    </div>
    <span class='badge $statusClass'>{$e['status']}</span>
  </div>

  <div class='row-actions'>
    <a class='edit-btn' href='editevent.php?id={$e['id']}'>Edit</a>
    <a class='del-btn' href='deleteevent.php?id={$e['id']}'
    onclick=\"return confirm('Delete event?')\">Delete</a>
  </div>
</div>
";
                }
                ?>

            </div>

        </div>
    </div>

    <footer>
        <p>© 2026 RC Middle School Admin Panel</p>
    </footer>

</body>

</html>