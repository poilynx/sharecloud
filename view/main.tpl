<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<link rel="stylesheet" href="{$HTTP_BASEDIR}/css/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="{$HTTP_BASEDIR}/css/font-awesome.min.css" type="text/css" />
<link rel="stylesheet" href="{$HTTP_BASEDIR}/css/main.css" type="text/css" />
{$resources}

<script type="text/javascript">
System.config.httpHost = "{$HTTP_BASEDIR}";
System.config.modRewrite = {if $MOD_REWRITE}true{else}false{/if};
System.config.maxUploadSize = {$MAX_UPLOAD_SIZE};
</script>
<script type="text/javascript">
{foreach $LangStrings as $key => $value}System.l10n.add('{$key}','{$value}');{/foreach}
</script>

</head>

<body>
<div class="page">    
    <nav class="navbar navbar-default" role="navigation">
    	<div class="container-fluid">
        	<div class="navbar-header">
            	<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#mainbar">
                	<span class="sr-only">Navigation</span>
                    <span class="icon-bar"> </span>
                    <span class="icon-bar"> </span>
                    <span class="icon-bar"> </span>
                </button>
                
                <a class="navbar-brand" href="{Router->build p1='BrowserController' p2='index'}">
                    sharecloud
                </a>
            </div>
           
            <div class="collapse navbar-collapse" id="mainbar">
            	<ul class="nav navbar-nav">
{foreach $Navigation as $elem}
					<li{if $elem->isCurrent()} class="active"{/if}>
                    	<a href="{Router->build p1={$elem->controller} p2={$elem->action}}" class="" {if $elem->ajax neq true} data-noajax="true"{/if}>{if $elem->fontawesome neq ''}<i class="fa fa-lg fa-{$elem->fontawesome}"></i> {/if}{$elem->label}</a>
                    </li>
{/foreach}                	
                </ul>
                
                {if $LoggedIn}
                <ul class="nav navbar-nav navbar-right">
                	<li class="dropdown">
                    	<a href="#" class="button-status" data-toggle="dropdown">{'NoUploads'|@lang}</a>
                        
                        <div id="upload-progress" class="dropdown-menu">
                            <div class="cloneable entry clearfix">
                                <p class="filename"></p>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemax="100" aria-valuemin="0" style="width:0%;">
                                        <span class="sr-only">0 %</span>
                                    </div>
                                </div>
                                
                                <p class="progress-status"></p>
                                <a href="#" class="btn btn-default btn-sm cancel-upload pull-right"><i class="fa fa-lg fa-remove"></i> {'Cancel'|@lang}</a>
                            </div>
                            
                            <p class="no-uploads">
                            	{'NoUploads'|@lang}
                            </p>
                        </div>
                    </li>
                    
                    <li class="dropdown">
                    	<a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        	<i class="fa fa-lg fa-user"></i> {$User->username} <b class="caret"></b>
                        </a>
                        
                        <ul class="dropdown-menu">
                        	<li>
                            	<a href="{Router->build p1='ProfileController' p2='index'}">
                            		<i class="fa fa-lg fa-cog"></i> {'MyProfile'|@lang}
                             	</a>
                            </li>
                            <li>
                                <a href="{Router->build p1='AuthController' p2='logout'}" data-noajax="true">
                                	<i class="fa fa-lg fa-sign-out"></i> {'LogOut'|@lang}
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                {/if}
            </div>
        </div>
    </nav>
    
    <div class="container-fluid alerts">
{if $infoMsg|default:FALSE }
        <div class="alert alert-info alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {$infoMsg}
        </div>
{/if}
{if $successMsg|default:FALSE }
        <div class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {$successMsg}
        </div>
{/if}
{if $errorMsg|default:FALSE }
        <div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {$errorMsg}
        </div>
{/if}
		<div class="cloneable alert alert-dismissable">
        	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <p class="msg"> </p>
        </div>
    </div>
    
    <div class="main container-fluid">
        {if $heading|default:FALSE}
        	<h3>{$heading}</h3>
        {/if}
        {block name=content}{/block}
    </div>
    
    <div class="push"> </div>
</div>

<footer>
	<div class="wrapper">
        © {'Y'|date} | <a href="http://192.168.102.13" data-noajax="true">东软网络安全工作室</a>
    </div>
</footer>

</body>
</html>
