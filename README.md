#這是什麼 ?

AECopyJoomla 是一個快速複製 Joomla 網站的 PHP 腳本

##他會幫你執行:

1. 複製檔案
2. 建立資料表
3. 輸出 sql ( 在新 joomla 資料夾內 /main.sql )
4. 資料表複製
5. 修改 joomla 設定 ( tmp, log, db name )

#使用方法

```
$ php main.php {原始 joomla 資料夾} {新的 joomla 資料夾} {新資料表名稱}
```