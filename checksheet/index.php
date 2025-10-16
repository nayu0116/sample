<?php
// セッションスタート
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.gc_divisor', 1);
session_start();
// セッションIDを新しいものに置き換える（★セッションハイジャック）
session_regenerate_id();
// クリックジャッキング対策
header("X-FRAME-OPTIONS: DENY");

// ログインしていないorログインから10時間以上経過していればログイン画面へ強制リダイレクト
if (!isset($_SESSION['loginName']) || (time() - $_SESSION["time"] > 36000)) {
  header('Location: ./login.php');
  exit();
}

// ログアウト
if (isset($_POST['logout'])) {

  // トークンチェック（★CSRF）
  if (empty($_SESSION['logout_token']) || ($_SESSION['logout_token'] !== $_POST['logout_token'])) exit('正しい手順で再度操作をお願いします');
  if (isset($_SESSION['logout_token'])) unset($_SESSION['logout_token']); //トークン破棄
  if (isset($_POST['logout_token'])) unset($_POST['logout_token']); //トークン破棄

  // セッションを破棄する（★セッションハイジャック）
  // セッション変数の中身をすべて破棄
  $_SESSION = array();
  // クッキーに保存されているセッションIDを破棄
  if (isset($_COOKIE["PHPSESSID"])) setcookie("PHPSESSID", '', time() - 1800, '/');
  // セッションを破棄
  session_destroy();

  // ログインページに戻る
  // GETでメッセージをlogin.phpに渡す
  header('Location: login.php?logout=1');
  exit();
}

// HTMLエスケープ
function h($s)
{
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}
// ログイン中のユーザー情報を表示（★クロスサイトスクリプティング）
$loginName = h($_SESSION['loginName']);

?>
<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/normalize.css">
  <link rel="stylesheet" href="./assets/css/style.css">
  <title>情報加工検査チェックシート</title>
</head>

<body>
  <header id="header">
    <div class="inner">
      <h2>情報加工検査チェックシート</h2>
      <div class="account-menu">
        <span class="account"><?php echo $loginName; ?>でログイン中</span>
        <button class="modal-open">ログアウト</button>
        <?php if ($loginName == "admin") echo '<a href="./control.php" class="control-btn">管理画面へ</a>' ?>
      </div>
    </div>
  </header>
  <main id="top-menu" class="container">
    <div class="inner">
      <?php
      if (isset($_SESSION['error-message'])) echo '<p class="err-msg">' . $_SESSION['error-message'] . '</p>';
      unset($_SESSION['error-message']);
      ?>
      <ul class="menu">
        <li>
          <a href="checksheet.php">
            <img src="./assets/img/new.svg" alt="">
          </a>
        </li>
        <li>
          <a href="search.php">
            <img src="./assets/img/seaech.svg" alt="">
          </a>
        </li>
      </ul>
    </div>
  </main>
  <div class="modal-bg">
    <div class="modal-content">
      <p>ログアウトしますか？</p>
      <form class="modal-btn" action="" method="post">
        <button class="cancel-btn" name="cancel">キャンセル</button>
        <button class="logout-btn" name="logout">ログアウト</button>
        <?php
        // 不正リクエストチェック用のトークン生成（★CSRF）
        $token = sha1(uniqid(mt_rand(), true));
        $_SESSION['logout_token'] = $token;
        echo '<input type="hidden" name="logout_token" value="' . $token . '" />';
        ?>
      </form>
    </div>
  </div>

  <script src="./assets/js/jquery-3.6.3.min.js"></script>
  <script src="./assets/js/modal.js"></script>
</body>

</html>