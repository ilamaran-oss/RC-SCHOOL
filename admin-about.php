<?php require "db.php"; ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Admin About Manager</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .admin-wrap {
            max-width: 1100px;
            margin: 40px auto;
        }

        .admin-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            margin-bottom: 25px;
        }

        .admin-card input,
        .admin-card textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            background: #1f4e79;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .row {
            border: 1px solid #eee;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <header class="main-header">
        <div class="logo">ABOUT PAGE ADMIN</div>
    </header>

    <div class="admin-wrap">

        <!-- CREATE CARD -->
        <div class="admin-card">
            <h2>Add About Card</h2>
            <form action="about_create.php" method="post">
                <input name="title" placeholder="Card Title" required>
                <textarea name="content" rows="4" placeholder="Card Content" required></textarea>
                <input name="display_order" placeholder="Display Order (1,2,3)">
                <button>Add Card</button>
            </form>
        </div>

        <!-- LIST CARDS -->
        <div class="admin-card">
            <h2>Manage About Cards</h2>

            <?php
            $r = $conn->query("SELECT * FROM about_cards ORDER BY display_order ASC");

            while ($a = $r->fetch_assoc()) {
                echo "
<div class='row'>
<b>{$a['title']}</b><br>
<small>Order: {$a['display_order']}</small>

<p>" . substr($a['content'], 0, 120) . "...</p>

<a href='about_edit.php?id={$a['id']}'>Edit</a> |
<a href='about_delete.php?id={$a['id']}' onclick=\"return confirm('Delete card?')\">Delete</a>
</div>
";
            }
            ?>

        </div>

    </div>
</body>

</html>