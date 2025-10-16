<?php
// セッションスタート
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.gc_divisor', 1);
session_start();
// セッションIDを新しいものに置き換える（★セッションハイジャック）
session_regenerate_id();
// クリックジャッキング対策
header("X-FRAME-OPTIONS: DENY");

// dbconnect.phpを読み込む
require_once 'dbconnect.php';

// HTMLエスケープ
function h($s)
{
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

// ログアウト完了メッセージの取得
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
  $logout_msg = 'ログアウトしました';
}

// ユーザーがログインフォームを送信した場合
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {

  // トークンチェック（★CSRF）
  if (empty($_SESSION['login_token']) || ($_SESSION['login_token'] !== $_POST['login_token'])) $err_msg = '正しい手順で再度操作をお願いします';
  if (isset($_SESSION['login_token'])) unset($_SESSION['login_token']); //トークン破棄
  if (isset($_POST['login_token'])) unset($_POST['login_token']); //トークン破棄

  // データベースに接続する
  $pdo = dbConnect();
  try {

    // POSTデータの取得
    $loginName = $_POST['loginName'];
    $password = $_POST['password'];

    $errors = [];

    if (empty($loginName)) {
      $errors['loginName'] = 'ユーザー名を入力してください';
    }
    if (empty($password)) {
      $errors['password'] = 'パスワードを入力してください';
    }

    // バリデーションエラーが無ければログイン処理
    if (count($errors) === 0) {
      // ログイン処理
      $sql = ('SELECT name, password FROM checkUserlist WHERE name = :NAME');
      $stmt = $pdo->prepare($sql);
      // プレースホルダーに値をセット
      $stmt->bindValue(':NAME', $loginName, PDO::PARAM_STR);
      // SQL実行
      $stmt->execute();

      // ログイン情報が正しいかをチェック
      $user_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (count($user_info) && password_verify($password, $user_info[0]['password'])) {
        // ログイン状態確認用にセッションにデータ保存（★ログイン機能の実現）
        $_SESSION['loginName'] = $user_info[0]['name'];
        $_SESSION['time'] = time();

        // ログイン後はトップページへ遷移する
        header('Location: ./index.php');
        exit();
      } else {
        $err_msg = 'ユーザー名またはパスワードが正しくありません';
      }
    }
  } catch (PDOException $e) {
    echo '接続失敗' . $e->getMessage();
    exit();
  }
  // DBとの接続を切る
  $pdo = null;
  $stmt = null;
}

?>
<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/normalize.css">
  <link rel="stylesheet" href="css/style.css">
  <title>情報加工検査チェックシート</title>
</head>

<body>
  <main id="login-page">
    <div class="login">
      <h1 class="title">情報加工検査チェックシート</h1>

      <!-- ログアウトメッセージ -->
      <?php if (!empty($logout_msg)) : ?>
        <p class="logout-msg"><?= h($logout_msg) ?></p>
        <script>
          // クエリパラメータを消す
          if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
          }
        </script>
      <?php endif; ?>

      <form action="" method="post" class="login-form">
        <div class="group">
          <input class="input-area" type="text" name="loginName" value="<?= h($loginName ?? '') ?>" autocomplete="off">
          <span class="highlight"></span><span class="bar"></span>
          <label class="input-label">ユーザー名</label>
          <span class="error"><?= $errors['loginName'] ?? '' ?></span>
        </div>
        <div class="group">
          <input class="input-area" type="password" id="input_pass" name="password" value="<?= h($password ?? '') ?>" autocomplete="off">
          <span class="highlight"></span><span class="bar"></span>
          <label class="input-label">パスワード</label>
          <span class="error"><?= $errors['password'] ?? $err_msg ?? '' ?></span>
        </div>
        <button name="login">ログイン</button>

        <?php
        // 不正リクエストチェック用のトークン生成（★CSRF）
        $token = bin2hex(random_bytes(32));
        $_SESSION['login_token'] = $token;
        echo '<input type="hidden" name="login_token" value="' . $token . '" />';
        ?>
      </form>
    </div>
  </main>
  <script src="./assets/js/jquery-3.6.3.min.js"></script>
  <script src="./assets/js/form-highlight.js"></script>

</body>

</html>