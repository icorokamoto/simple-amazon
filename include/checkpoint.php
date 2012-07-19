<?php

/*
Copyright (c) 2009 sakuratan.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

define('CHECKPOINT_DEBUG', false);

/**
 * アクセス直列化のためのロックファイルを更新する。
 *
 * @param  resource $fh       ロックファイルのハンドル
 * @param  boolean  $aborted  true にすると異常終了として記録
 * @return boolean  エラー時は false、それ以外は true
 */
function checkpoint_update($fh, $aborted=false)
{
    if (!ftruncate($fh, 0))
	return false;
    if (fseek($fh, 0, SEEK_SET) != 0)
	return false;
    $data = array(microtime(true), $aborted);
    if (fwrite($fh, serialize($data)) === false)
	return false;
    if (!fflush($fh))
	return false;
    return true;
}

function _checkpoint_open_internal($lockfile, $interval, $aborted, $margin=0.0)
{
    // ロックファイルを開く
    $fh = @fopen($lockfile, 'r+');
    if (!$fh) {
		// 開けなかったら作る
		$fh = @fopen($lockfile, 'x+');
		if ($fh) {
			if (!flock($fh, LOCK_EX)) {
				unlink($lockfile);
				fclose($fh);
				return false;
			}
			if (!checkpoint_update($fh, $aborted)) {
				unlink($lockfile);
				fclose($fh);
				return false;
			}
			return $fh;
		}

		// 作れなかったら（他プロセスが作ってる可能性があるので）
		// もう一度開いてみてダメなら諦める
		$fh = fopen($lockfile, 'r+');
		if (!$fh)
			return false;
	}

	// ファイルをロック
	if (!flock($fh, LOCK_EX)) {
		fclose($fh);
		return false;
	}
	if (fseek($fh, 0, SEEK_SET) != 0) {
		fclose($fh);
		return false;
	}

	// 前回の API 実行時間を読み込む
	$ctx = '';
	while (!feof($fh))
		$ctx .= fread($fh, 8192);
	$arr = unserialize($ctx);
	if ($arr === false) {
		fclose($fh);
		return false;
	}
	list($prev_at, $prev_aborted) = $arr;

	if ($prev_aborted) {
		// 前回異常終了なら $margin 秒 sleep
		$wait = $margin;
		if ($wait > 0.0)
			trigger_error("Previous call was aborted, sleep {$margin} seconds", E_USER_NOTICE);
		} elseif ($interval > 0.0 && $prev_at >= $interval) {
			// interval 秒間に一回だけ実行されるように sleep
			$rel = $prev_at - fmod($prev_at, $interval) + $interval;
			$wait = $rel - microtime(true);
			if (CHECKPOINT_DEBUG && $wait > 0.0)
				trigger_error("DEBUG: normal wait={$wait}", E_USER_NOTICE);
		} else {
		$wait = 0.0;
	}

    if ($wait > 0.0) {
	$f = floor($wait);
	$g = $wait - $f;
	if ($g > 0.0)
	    usleep((int)($g * 1000000));
	if ($f > 0.0)
	    sleep((int)$f);
    }

    if (!checkpoint_update($fh, $aborted)) {
	fclose($fh);
	return false;
    }
    return $fh;
}

/**
 * アクセス直列化のためのロックファイルを開く。
 *
 * @param  string  $lockfile  直列化に使用するロックファイルのパス
 * @param  float   $interval  直列化時の実行間隔秒
 * @param  float   $margin    前回異常終了時の実行遅延時間(秒)
 * @return mixed   エラー時は false、それ以外はロックファイルのハンドル
 */
function checkpoint_open($lockfile, $interval, $margin=0.0)
{
    return _checkpoint_open_internal($lockfile, $interval, true, $margin);
}

/**
 * アクセス直列化のためのロックファイルを閉じる。
 *
 * @param  resource $fh  ロックファイルのハンドル
 * @return boolean  エラー時は false、それ以外は true
 */
function checkpoint_close($fh)
{
    if (!checkpoint_update($fh)) {
	fclose($fh);
	return false;
    }
    return fclose($fh);
}

/**
 * スクリプトの実行を flock を使って直列化します。以下のように直列化箇所の直前
 * で 呼び出してください。
 *
 *	checkpoint('/tmp/mylockfile', 1);
 *	// 直列化箇所
 *
 * $interval 引数が 0 より大きい場合は、直列化箇所が $interval 秒に1回以上呼び
 * 出されないようウェイトします。
 * ウェイトの計算は $interval 秒に丸められます。単純に $interval 秒ウェイトす
 * るのではなく、Unix タイムスタンプを起点として checkpoint の実行完了が
 * $interval 秒に1回発生するようウェイトを調整します。
 * 例えば、前回の checkpoint 呼び出しが 10000.5 秒で $interval が 1.0 の場合、
 * 10001 秒までウェイトするよう sleep します。
 *
 * 直列化箇所終了時の時刻を元にウェイトする場合や、エラー処理を厳密に行いたい
 * 場合は、checkpoint_open と checkpoint_close を使用してください。以下の例で
 * は、直列化箇所で異常終了した場合に次回の実行を10秒遅延します。
 *
 *	$fh = checkpoint_open('/tmp/mylockfile', 1, 10) or die;
 *	// 直列化箇所
 *	checkpoint_close($fh);
 *
 * @param  string  $lockfile  直列化に使用するロックファイルのパス
 * @param  float   $interval  直列化時の実行間隔秒
 * @return boolean エラー時は false、それ以外は true
 */
function checkpoint($lockfile, $interval)
{
	$fh = _checkpoint_open_internal($lockfile, $interval, false);
	if (!$fh)
	return false;
	return fclose($fh);
}

?>
