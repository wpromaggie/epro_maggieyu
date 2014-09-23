<?php

class wid_quick_search
{
	public function output($cur_dept = false)
	{
		$search_dept = util::unempty($_POST['search_dept'], $cur_dept, 'all');
		?>
		<span id="quick_search" ejo>
			<input type="text" id="quick_search_text" name="quick_search" value="<?= $_POST['quick_search'] ?>" />
			<?= cgi::html_select('search_dept_select', array_merge(array('all')), $search_dept); ?>
			<input type="submit" a0="action_quick_search" a0href="/product/search/" id="quick_search_submit" value="Quick Search" />
		</span>
		<?php
	}
}

?>