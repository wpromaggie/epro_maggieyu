<?php
/**
 * so much development
 */
class mod_camp_dev extends module_base
{
	public function display_index()
	{
		?>
		<h1>A Development Camp!</h1>
		
		<fieldset class="group">
			<legend>The Lists</legend>
			<div id="list_commands">
				<input type="button" id="the_add_button" class="serious_button" value="Add A List" />
				<input type="button" id="the_clearall_button" class="serious_button" value="Clear All Lists" />
			</div>
			<div id="list_container"></div>
		</fieldset>
		
		<fieldset class="group">
			<legend>Match Types</legend>
			<p><input type="checkbox" name="broad" id="broad" value="1" checked /> <label for="broad">Broad</label></p>
			<p><input type="checkbox" name="exact" id="exact" value="1" /> <label for="exact">Exact</label></p>
			<p><input type="checkbox" name="phrase" id="phrase" value="1" /> <label for="phrase">Phrase</label></p>
			<p><input type="checkbox" name="mod_broad" id="mod_broad" value="1" /> <label for="mod_broad">Mod Broad</label>
				<div id="mod_broad_options">
					<p>
						<input type="radio" name="mod_broad_all_or_some" id="mod_broad_all" value="all" checked />
						<label for="mod_broad_all">All</label>
					</p>
					<p>
						<input type="radio" name="mod_broad_all_or_some" id="mod_broad_some" value="some" />
						<label for="mod_broad_some">Lists:</label>
						<div id="mod_broad_lists">
						</div>
					</p>
				</div>
			</p>
		</fieldset>
		
		<fieldset class="group">
			<legend>Results</legend>
			<table>
				<tbody>
					<tr valign="top">
						<td><input type="button" id="the_big_button" class="serious_button" value="Generate Phrases" /></td>
						<td>
							<table id="keyword_progress_table" class="hide">
								<tbody>
									<tr id="keyword_progress_row">
										<td class="progress_cell_on"></td><td class="progress_spacer"></td>
										<td class="progress_cell"></td><td class="progress_spacer"></td>
										<td class="progress_cell"></td><td class="progress_spacer"></td>
										<td class="progress_cell"></td><td class="progress_spacer"></td>
										<td class="progress_cell"></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
			<p></p>
			<table>
				<tbody>
					<tr>
						<td>
							<table width="100%">
								<tbody>
									<tr>
										<td width="25%" id="generated_count_cell">0</td>
										<th width="50%">Generated List</th>
										<td width="25%" align="right"><input type="button" id="clear_generated_btn" class="small_button" value="Clear" /></td>
									</tr>
								</tbody>
							</table>
						</td>
						<td></td>
						<td>
							<table width="100%">
								<tbody>
									<tr>
										<td width="25%" id="master_count_cell">0</td>
										<th width="50%">Master List</th>
										<td width="25%" align="right"><input type="button" id="clear_master_btn" class="small_button" value="Clear" /></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td><textarea name="lgen" id="gen_list" class="kw_list"></textarea></td>
						<td><input type="button" id="gen_to_master_btn" class="small_button" value="->" /></td>
						<td><textarea name="lmaster" id="master_list" class="kw_list"></textarea></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div class="clear"></div>
		<?php
	}
}

?>	