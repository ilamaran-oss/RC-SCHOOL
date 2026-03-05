<?php require "db.php";
$id = (int)$_GET['id'];
$r = $conn->query("SELECT * FROM about_cards WHERE id=$id");
$a = $r->fetch_assoc();
?>

<form method="post" action="about_update.php">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input name="title" value="<?php echo $a['title']; ?>">
    <textarea name="content"><?php echo $a['content']; ?></textarea>
    <input name="display_order" value="<?php echo $a['display_order']; ?>">
    <button>Update</button>
</form>