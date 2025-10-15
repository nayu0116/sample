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

// dbconnect.phpを読み込む
require_once 'dbconnect.php';
// データベースに接続する
$pdo = dbConnect();

// HTMLエスケープ
function h($s)
{
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}
// ログイン中のユーザー情報を表示（★クロスサイトスクリプティング）
$loginName = h($_SESSION['loginName']);

try {
  // 取得するテーブル名とラベルの対応表
  $tables = [
    'checkUserlist' => 'アカウント',
    'kensa_sekinin' => '検査責任者',
    'kensa_tantou' => '検査担当者',
    'sagyou_tantou' => '作業担当者'
  ];
  $data = [];

  $class = $_POST['class'] ?? '';

  foreach ($tables as $table => $title) {
    $sql = "SELECT name FROM $table";  // nameカラムのみ取得
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN); // nameカラムだけ取得
  }
} catch (PDOException $e) {
  echo "データベースエラー: " . $e->getMessage();
  exit;
}

// 新規登録
if (isset($_POST['addition'])) {
  if (!isset($_POST['session_token']) || $_POST['session_token'] !== $_SESSION['session_token']) {

    $_SESSION['err_msg'] = '正しい手順で再度操作をお願いします';
  } else {

    // POSTデータの取得
    $class = $_POST['class'];
    $name = $_POST['name'];

    $errors = [];

    if (empty($_POST['password'])) {
      $password = null;
    } else {
      $password = $_POST['password'];
    }

    if (empty($class)) {
      $errors['class'] = '区分を選択してください';
    }
    if (empty($name)) {
      $errors['name'] = '名前を入力してください';
    }
    if ($class === 'checkUserlist') {
      if (empty($password)) {
        $errors['password'] = 'パスワードを入力してください';
      } else if (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $errors['password'] = '半角英数字で入力してください';
      }
    }

    // バリデーションエラーが無ければ登録処理
    if (count($errors) === 0) {
      try {
        /**
         * 情報重複チェック
         * 入力された名前がすでに登録済みかどうかをチェックする
         */
        // 許可するテーブル名を事前にリスト化
        $allowed_classes = ['checkUserlist', 'kensa_sekinin', 'kensa_tantou', 'sagyou_tantou'];

        if (in_array($class, $allowed_classes)) {
          $sql = "SELECT name FROM $class WHERE name = :name;";
          $stmt = $pdo->prepare($sql);
          // プレースホルダーに値をセット
          $stmt->bindValue(':name', $_POST['name']);
          // SQL実行
          $stmt->execute();
          // ユーザ情報の取得
          $user_info = $stmt->fetchAll();

          // ユーザ情報が取得できている＝件数が「1」の場合はエラーメッセージを返す
          if (count($user_info)) {
            $err_msg = 'その名前はすでに使用されています';
          } else {


            // checkUserlistの場合パスワードも登録する
            if ($class === 'checkUserlist') {
              // パスワードをハッシュ化（★SQLインジェクション）
              $hashed_password = password_hash($password, PASSWORD_DEFAULT);

              $sql = "INSERT INTO $class (name, password) VALUES (:name, :password)";
              $stmt = $pdo->prepare($sql);
              $stmt->bindValue(':name', $name, PDO::PARAM_STR);
              $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
            } else {
              // それ以外は名前だけ登録
              $sql = "INSERT INTO $class (name) VALUES (:name)";
              $stmt = $pdo->prepare($sql);
              $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            }

            // SQL実行
            $stmt->execute();

            $_SESSION['msg'] = "会員登録が完了しました";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
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
  }
}

//ワンタイムトークン生成（URL直打ち対策用）
$token = openssl_random_pseudo_bytes(16);
$session_token = bin2hex($token);
$_SESSION['session_token'] = $session_token;

?>
<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/normalize.css">
  <link rel="stylesheet" href="./assets/css/styleseat.css">
  <title>情報加工課検査チェックシート</title>
</head>

<body>
  <header id="header">
    <div class="inner">
      <h2>情報加工検査チェックシート</h2>
      <div class="account-menu">
        <span class="account"><?= $loginName; ?>でログイン中</span>
        <a href="./index.php" class="top-btn">トップへ戻る</a>
      </div>
    </div>
  </header>
  <main class="container">
    <div class="inner">
      <section id="addition">
        <h3 class="title">管理画面</h3>
        <form class="search-form" method="post" action="">
          <dl class="item">
            <dt class="label">
              <label for="hinmei">区分</label>
            </dt>
            <dd class="box select">
              <select class="input" name="class" id="class">
                <option value="" hidden>選択してください</option>
                <?php foreach ($tables as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $class === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="error"><?= $errors['class'] ?? '' ?></span>
            </dd>
          </dl>
          <dl class="item">
            <dt class="label">
              <label for="name">名前</label>
            </dt>
            <dd class="box">
              <input class="input" type="text" name="name" value="<?= h($name ?? '') ?>" autocomplete="off">
              <span class="error"><?= $errors['name'] ?? '' ?></span>
            </dd>
          </dl>
          <dl class="item login-pass">
            <dt class="label">
              <label for="password">パスワード（半角英数字で入力してください）</label>
            </dt>
            <dd class="box">
              <input class="input" type="text" name="password" value="<?= h($password ?? '') ?>" autocomplete="off">
              <span class="error"><?= $errors['password'] ?? '' ?></span>
            </dd>
          </dl>
          <?php if (isset($err_msg)) echo '<p class="err-msg">' . $err_msg . '</p>'; ?>
          <div class="search-btn-flex">
            <input type="hidden" name="session_token" value="<?= $session_token; ?>" />
            <button class="search-btn" name="addition">登録</button>
          </div>
        </form>
      </section>
      <section id="control">
        <!-- メッセージ -->
        <?php
        if (isset($_SESSION['err_msg'])) echo '<p class="err-msg">' . $_SESSION['err_msg'] . '</p>';
        unset($_SESSION['err_msg']);
        if (isset($_SESSION['msg'])) echo '<p class="msg">' . $_SESSION['msg'] . '</p>';
        unset($_SESSION['msg']);
        ?>
        <dl class="control-menu">
          <?php foreach ($tables as $table => $title): ?>
            <dt class="control-title"><?= h($title); ?></dt>
            <dd>
              <?php if (!empty($data[$table])): ?>
                <ul class="user">
                  <?php foreach ($data[$table] as $name): ?>
                    <li class="userlist">
                      <p class="name"><?= h($name); ?></p>
                      <input type="hidden" name="session_token" value="<?= $session_token; ?>" />
                      <button class="account-delete modal-open" data-account="<?= h($title); ?>" data-username="<?= h($name); ?>">削除</button>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                データがありません
              <?php endif; ?>
            </dd>
          <?php endforeach; ?>
        </dl>
      </section>
    </div>
  </main>
  <!-- モーダル -->
  <div id="delete-modal" class="modal-bg">
    <div class="modal-content">
      <p id="modal-text"></p>
      <form class="modal-btn" action="./delete.php" method="post">
        <input type="hidden" name="account" id="modal-account">
        <input type="hidden" name="username" id="modal-username">
        <button type="button" class="cancel-btn" id="modal-cancel">キャンセル</button>
        <button type="submit" class="delete-btn" name="delete">削除</button>
      </form>
    </div>
  </div>

  <script src="./assets/js/jquery-3.6.3.min.js"></script>
  <script src="./assets/js/control.js"></script>
  <script src="./assets/js/modal.js"></script>
</body>

</html>