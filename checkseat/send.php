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

// HTMLエスケープ
function h($s)
{
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}
// ログイン中のユーザー情報を表示（★クロスサイトスクリプティング）
$loginName = h($_SESSION['loginName']);

if (!isset($_POST['session_token']) || $_POST['session_token'] !== $_SESSION['session_token']) {

  $_SESSION['error-message'] = '正しい手順で再度操作をお願いします';
  header('Location: ./index.php');
  exit();
} else {
  try {
    // データベースに接続する
    $pdo = dbConnect();

    //index.phpの値を取得
    $Aday = isset($_POST['Aday']) && !empty($_POST['Aday']) ? $_POST['Aday'] : null;
    $BCday = isset($_POST['BCday']) && !empty($_POST['BCday']) ? $_POST['BCday'] : null;
    $kousei = isset($_POST['kousei']) && !empty($_POST['kousei']) ? $_POST['kousei'] : null;

    $sales = $_POST['number']['sales'] ?? '';
    $year = $_POST['number']['year'] ?? '';
    $orders = $_POST['number']['orders'] ?? '';
    $number = "{$sales}-{$year}-{$orders}";

    $type = isset($_POST['type']) && !empty($_POST['type']) ? $_POST['type'] : null;
    $naikou = isset($_POST['naikou']) && !empty($_POST['naikou']) ? $_POST['naikou'] : null;
    $hinmei = isset($_POST['hinmei']) && !empty($_POST['hinmei']) ? $_POST['hinmei'] : null;
    $orisuu = isset($_POST['orisuu']) && !empty($_POST['orisuu']) ? $_POST['orisuu'] : null;
    $omote = isset($_POST['omote']) && !empty($_POST['omote']) ? $_POST['omote'] : null;
    $ura = isset($_POST['ura']) && !empty($_POST['ura']) ? $_POST['ura'] : null;
    $kensa = isset($_POST['kensa']) && !empty($_POST['kensa']) ? $_POST['kensa'] : null;

    $comment = isset($_POST['comment']) && !empty($_POST['comment']) ? $_POST['comment'] : null;
    $kensa_sekinin = isset($_POST['kensa_sekinin']) && !empty($_POST['kensa_sekinin']) ? $_POST['kensa_sekinin'] : null;
    $kensa_tantou = '';
    $sagyou_tantou = '';

    $triangles = [];
    $checklists = [];
    for ($i = 1; $i <= 50; $i++) {
      if ($kensa === "検査不要の指示あり" && $i >= 11) {
        // 11〜50は強制的に「未選択」「未チェック」にする
        $triangles[] = "未選択";
        $checklists[] = "未チェック";
      } else {
        // triangles の処理
        if (isset($_POST['triangles'][$i])) {
          // 単一の値でも統一して配列として扱う
          $triangles[] = h($_POST['triangles'][$i]);
        } else {
          // 未送信のデータは "未選択" にする（データ数を統一）
          $triangles[] = "";
        }

        // checklists の処理
        if (isset($_POST['checklists'][$i])) {
          if (is_array($_POST['checklists'][$i])) {
            $checklists[] = implode(', ', array_map('h', $_POST['checklists'][$i]));
          } else {
            $checklists[] = h($_POST['checklists'][$i]);
          }
        } else {
          // 未送信のデータは空欄にする
          $checklists[] = "";
        }
      }
    }

    // カンマ区切りの文字列に変換
    $triangles = implode(', ', $triangles);
    $checklists = implode(', ', $checklists);

    if (isset($_POST['kensa_tantou']) && is_array($_POST['kensa_tantou'])) {
      $kensa_tantou = implode(', ', $_POST['kensa_tantou']);
    }
    if (isset($_POST['sagyou_tantou']) && is_array($_POST['sagyou_tantou'])) {
      $sagyou_tantou = implode(', ', $_POST['sagyou_tantou']);
    }

    $sql = "INSERT INTO checkseat (Aday, BCday, kousei, number, type, naikou, hinmei, orisuu, omote, ura, kensa, triangles, checklists, comment, kensa_sekinin, kensa_tantou, sagyou_tantou) VALUES (:Aday, :BCday, :kousei, :number, :type, :naikou, :hinmei, :orisuu, :omote, :ura, :kensa, :triangles, :checklists, :comment, :kensa_sekinin, :kensa_tantou, :sagyou_tantou)";

    // テーブルに登録するINSERT INTO文を変数に格納VALUESはプレースフォルダーで空の値を入れとく
    $stmt = $pdo->prepare($sql); //値が空のままSQL文をセット
    $params = array(
      ':Aday' => $Aday,
      ':BCday' => $BCday,
      ':kousei' => $kousei,
      ':number' => $number,
      ':type' => $type,
      ':naikou' => $naikou,
      ':hinmei' => $hinmei,
      ':orisuu' => $orisuu,
      ':omote' => $omote,
      ':ura' => $ura,
      ':kensa' => $kensa,
      ':triangles' => $triangles,
      ':checklists' => $checklists,
      ':comment' => $comment,
      ':kensa_sekinin' => $kensa_sekinin,
      ':kensa_tantou' => $kensa_tantou,
      ':sagyou_tantou' => $sagyou_tantou
    ); // 挿入する値を配列に格納
    $stmt->execute($params); //挿入する値が入った変数をexecuteにセットしてSQLを実行

    $result = "登録完了";
  } catch (PDOException $e) {
    $result = "接続エラー";
  }
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/normalize.css">
  <link rel="stylesheet" href="./css/styleseat.css">
  <title>情報加工検査チェックシート</title>
</head>

<body>
  <header id="header">
    <div class="inner">
      <h2>情報加工検査チェックシート</h2>
      <form class="account-menu" action="#" method="post">
        <span class="account"><?= $loginName; ?>でログイン中</span>
        <a href="./index.php" class="top-btn">トップへ戻る</a>
        <?php
        // 不正リクエストチェック用のトークン生成（★CSRF）
        $token = sha1(uniqid(mt_rand(), true));
        $_SESSION['logout_token'] = $token;
        echo '<input type="hidden" name="logout_token" value="' . $token . '" />';
        ?>
      </form>
    </div>
  </header>
  <main id="completion-page">
    <div class="completion-message">
      <?php if ($result === "登録完了") : ?>
        <p class="message-title">登録完了しました</p>
        <p class="message"><span style="display: inline-block;">チェックシートをデータベースへ登録しました。</span><span style="display: inline-block;">入力内容を確認する場合は下のリンクから検索してください。</span></p>
      <?php elseif ($result = "接続エラー") : ?>
        <p class="message-title">登録できませんでした</p>
        <p class="message"><span style="display: inline-block;">データベースへの登録でエラーが発生しました。</span><span style="display: inline-block;">この画面が表示された場合は中西までお願いします。</span></p>
      <?php endif; ?>
      <a href="./search.php" class="top-btn">検索画面へ</a>
    </div>
  </main>
</body>

</html>