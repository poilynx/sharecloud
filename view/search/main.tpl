<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>{$title}</title>
<link rel="stylesheet" href="{$HTTP_BASEDIR}/css/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="{$HTTP_BASEDIR}/css/main.css" type="text/css" />

<script type="text/javascript">
System.config.httpHost = "{$HTTP_BASEDIR}";
System.config.modRewrite = {$MOD_REWRITE};
</script>
<script type="text/javascript">
{foreach $LangStrings as $key => $value}System.l10n.add('{$key}','{$value}');{/foreach}
</script>

</head>

<body>
	<form method="get" action="{$HTTP_BASEDIR}/search">
		<input type="textbox" name="keyword"></input>
		<input type="submit" value="@Search"></input>
	</form>
	<table>
		<tr>
			<th>filename</th><th>size</th>
		</tr>
		{foreach $files as $file}
		<tr><td>{$file->filename}</td><td>{Utils::formatBytes($file->size)}</td></tr>
		{/foreach}

	</table>
    <footer>
        <div class="wrapper">
            © {'Y'|date} | <a href="http://192.168.102.13" data-noajax="true">东软网络安全工作室</a>
        </div>
    </footer>
</div>
</body>
</html>
