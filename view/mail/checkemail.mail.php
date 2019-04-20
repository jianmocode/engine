<?php
$_mail_subject ='拜师预约成功提醒';
$_mail_body =<<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN       " "http://www.w3.org/TR/html4/loose.dtd">
<html lang="zh-cn">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
    <div>
        <h3>姓名: $name </h3>
        <p> 性别: $sex </p>
    </div>
</body>
</html>
EOT;
?>