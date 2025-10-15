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

// データベースから作業担当者を取得
$query = "SELECT * FROM sagyou_tantou";
$stmt = $pdo->query($query);
$rec_lists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 「検索」ボタン押下時
if (isset($_POST["search"])) {
  // エラーメッセージを初期化
  $err_msg = '';
  $search_number = '';

  // 受注番号のチェック
  $sales = $_POST['search_number']['sales'] ?? '';
  $year = $_POST['search_number']['year'] ?? '';
  $orders = $_POST['search_number']['orders'] ?? '';
  // 他の項目
  $search_productname = $_POST['search_productname'] ?? '';
  $search_name = $_POST['search_name'] ?? '';

  if (!empty($sales) || !empty($year) || !empty($orders)) {
    if (!empty($sales) && !empty($year) && !empty($orders)) {
      $search_number = "{$sales}-{$year}-{$orders}";
    } else {
      $err_msg = '受注番号を全て入力してください';
      $success = '検索解除';
    }
  }

  // すでにエラーメッセージがある場合は検索処理を中断
  if ($err_msg === '') {

    // 少なくとも1つの項目が入力されているかチェック
    if (!empty($search_number) || !empty($search_productname) || !empty($search_name)) {
      // SQLクエリの作成
      $sql = "SELECT * FROM checkseat WHERE 1";
      $params = [];

      if (!empty($search_number)) {
        $sql .= " AND number LIKE ?";
        $params[] = "%{$search_number}%";
      }
      if (!empty($search_productname)) {
        $search_productname = trim($search_productname);
        $search_productname = mb_convert_kana($search_productname, 'KVas');
        $sql .= " AND hinmei LIKE ?";
        $params[] = "%{$search_productname}%";
        var_dump($search_productname);
      }
      if (!empty($search_name)) {
        $sql .= " AND sagyou_tantou LIKE ?";
        $params[] = "%{$search_name}%";
      }

      $rec = $pdo->prepare($sql);
      $rec->execute($params);
      $rec_list = $rec->fetchAll(PDO::FETCH_ASSOC);

      if (empty($rec_list)) {
        $err_msg = '該当するデータがありませんでした';
      }
      $success = '検索解除';
    } else {
      $err_msg = '1つ以上の項目を入力してください';
    }
  }
  // エラーがある場合は全データを表示（検索結果をリセット）
  if ($err_msg !== '') {
    $sql = 'SELECT * FROM checkseat WHERE 1';
    $rec = $pdo->prepare($sql);
    $rec->execute();
    $rec_list = $rec->fetchAll(PDO::FETCH_ASSOC);
  }
} else {
  // 「検索」ボタンが押されていない場合全件表示
  $sql = 'SELECT * FROM checkseat WHERE 1';
  $rec = $pdo->prepare($sql);
  $rec->execute();
  $rec_list = $rec->fetchAll(PDO::FETCH_ASSOC);
}

// データベース切断
$pdo = null;

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
  <title>情報加工検査チェックシート</title>
</head>

<body>
  <header id="header">
    <div class="inner">
      <h2>情報加工検査チェックシート</h2>
      <div class="account-menu">
        <span class="account"><?= $loginName; ?>でログイン中</span>
        <a href="./index.php" class="top-btn">トップへ戻る</a>
        <?php
        // 不正リクエストチェック用のトークン生成（★CSRF）
        $token = sha1(uniqid(mt_rand(), true));
        $_SESSION['logout_token'] = $token;
        echo '<input type="hidden" name="logout_token" value="' . $token . '" />';
        ?>
      </div>
    </div>
  </header>
  <!--検索-->
  <main id="search" class="container">
    <div class="inner">
      <form class="search-form" method="post" action="search.php">
        <dl class="item">
          <dt class="label">
            <label for="number">受注番号（全て入力してください）</label>
          </dt>
          <dd class="box number select">
            <input class="datalist input" type="text" name="search_number[sales]" id="number" list="sales" placeholder="選択または入力" value="<?= h($sales ?? '') ?>" autocomplete="off">
            <datalist id="sales">
              <option value="KB"></option>
              <option value="KC"></option>
              <option value="KD"></option>
              <option value="KJ"></option>
              <option value="KO"></option>
              <option value="KR"></option>
              <option value="KT"></option>
              <option value="KV"></option>
              <option value="KW"></option>
              <option value="KX"></option>
              <option value="KZ"></option>
            </datalist>-
            <input class="input" type="number" name="search_number[year]" value="<?= h($year ?? '') ?>" min="1" step="1" data-group="search_number" autocomplete="off">-
            <input class="input" type="number" name="search_number[orders]" value="<?= h($orders ?? '') ?>" min="1" step="1" data-group="search_number" autocomplete="off">
          </dd>
        </dl>
        <dl class="item">
          <dt class="label">
            <label for="hinmei">品名</label>
          </dt>
          <dd class="box">
            <input class="input" type="text" name="search_productname" value="<?= h($search_productname ?? '') ?>" autocomplete="off">
          </dd>
        </dl>
        <dl class="item">
          <dt class="label">
            <label for="hinmei">作業担当者</label>
          </dt>
          <dd class="box input select">
            <select class="sign-box" name="search_name">
              <option value="" hidden>選択してください</option>
              <?php foreach ($rec_lists as $recs) { ?>
                <option value="<?= h($recs['name']); ?>" <?= h($search_name ?? '') === h($recs['name']) ? 'selected' : '' ?>><?= h($recs['name']); ?></option>
              <?php } ?>
            </select>
          </dd>
        </dl>
        <?php if (isset($err_msg)) echo '<p class="err-msg">' . $err_msg . '</p>'; ?>
        <div class="search-btn-flex">
          <button class="search-btn <?= (isset($success) && $success == '検索解除') ? 'center' : '' ?>" name="search">検索</button>
          <?php if (isset($success) && $success == '検索解除') { ?>
            <a class="search-cancel" href="search.php">検索を解除</a>
          <?php } ?>
        </div>
      </form>
      <table id="sort_table" class="result">
        <tr>
          <th class="sales-number column">受注番号</th>
          <th class="hinmei column">品名</th>
          <th class="sagyou_tantou column">作業担当者</th>
          <th class="update"></th>
        </tr>
        <!-- MySQLデータを表示 -->
        <?php foreach ($rec_list as $rec) { ?>
          <tr>
            <td class="sales-number"><?= h($rec['number']); ?></td>
            <td class="hinmei"><?= h($rec['hinmei']); ?></td>
            <td class="sagyou_tantou"><?= h($rec['sagyou_tantou']); ?></td>
            <td class="update"><a href="edit.php?id=<?php print($rec['id']) ?>">編集</a></td>
          </tr>
        <?php } ?>
      </table>
      <button class="csv-btn" id="csv">CSV出力</button>
      <a style="display: none" id="downloader" href="#"></a>
    </div>
  </main>
  <script src="./assets/js/jquery-3.6.3.min.js"></script>
  <script src="./assets/js/sort.js"></script>
  <script src="./assets/js/csv_export.js"></script>
</body>

</html>