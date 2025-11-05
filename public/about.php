<?php 
include __DIR__ . '/../../includes/config.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>About | UrbanThrift</title>
<style>
body { background:#0A0A0F; color:#fff; font-family:Arial; }
.container {
    width:80%;
    margin:40px auto;
    background:#12121A;
    padding:30px;
    border-radius:10px;
    text-align:center;
}
h2 { color:#C77DFF; }

.team-section {
    margin-top:30px;
    display:flex;
    justify-content:center;
    gap:30px;
    flex-wrap:wrap;
}

.team-card {
    background:#1A1A24;
    width:260px;
    padding:20px;
    border-radius:10px;
    border:1px solid #9b4de0;
    transition:0.3s ease;
}
.team-card:hover {
    background:#242434;
    transform:translateY(-5px);
}
.avatar {
    width:100px;
    height:100px;
    margin:auto;
    background:#9b4de0;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:42px;
    font-weight:bold;
    color:#fff;
}
.name { margin-top:15px; font-size:20px; font-weight:bold; color:#C77DFF; }
.role { font-size:14px; color:#cbb1ff; margin-top:5px; }
</style>
</head>
<body>

<div class="container">
    
    <h2>About UrbanThrift</h2>
    <p>
        UrbanThrift is your go-to sustainable fashion platform dedicated to providing
        stylish thrift clothing while helping small businesses manage inventory,
        customers, expenses, and transactions with ease.
    </p>

    <h2 style="margin-top:40px;">Meet the Team</h2>

    <div class="team-section">

        <div class="team-card">
            <div class="avatar">S</div>
            <div class="name">Sedriel H. Navasca</div>
            <div class="role">Backend Developer</div>
        </div>

        <div class="team-card">
            <div class="avatar">A</div>
            <div class="name">Ardee Jhade B. Orlanda</div>
            <div class="role">Frontend Developer</div>
        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
