<?php
final class SearchController extends ControllerBase {
	protected function onBefore($action = '') {
		#parent::checkIfAdmin();
	}

		
	public function search() {
		$keyword = Utils::getGET('keyword');
		$keywords = explode(' ',$keyword);
		$files = File::search($keywords);
		//foreach($files as $file) {
		//	var_dump($file);
		//}
		
		$smarty = new Template;
		$smarty->assign('title','@File Found');
		$smarty->assign('files',$files);
		$smarty->display('search/main.tpl');
	}

	public function onFinished($action = '') {

	}
	

}
?>
