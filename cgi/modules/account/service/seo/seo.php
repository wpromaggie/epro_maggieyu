<?php

class mod_account_service_seo extends mod_account_service
{
	protected $m_name = 'seo';

	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'info';
	}

	// todo: do we still need this badge stuff?

        protected function show_badge_form(){

            if(isset($_POST['submit'])){
                $badge_code = self::create_update_badge($_POST, $_FILES['image']);
            } else if($_POST['delete']) {
                db::exec("DELETE FROM seo_badges WHERE id={$_POST['badge_id']}");
                header("Location: /seo/show_badge_gallery/");
            } else if(isset($_POST['badge_id'])){
                //we came here through the gallery
                $badge = db::select_row("SELECT * FROM seo_badges WHERE id={$_POST['badge_id']}", "ASSOC");
                $_POST['alt_text'] = $badge['alt_text'];
                $_POST['url'] = $badge['url'];
                $_POST['title'] = $badge['title'];
                $_POST['filename'] = $badge['filename'];
                $badge_code = self::get_bage_code($_POST);
            }

        ?>
                <h2>Lets Create Some Links!</h2>
                <p>Please feed me some info and I'll hook you up... (NOMNOM)</p>

                <div id="badge_form">
                    <input type="hidden" id="badge_id" name="badge_id" value="<?php echo $_POST['badge_id']; ?>" />
                    <fieldset>
                        <legend>Image Stuff</legend>
                        <div>
                            <label>Image: </label>
                            <input type="file" name="image" id="image" class="inputText" value="" />
                            <input type="hidden" id="filename" name="filename" value="<?php echo $_POST['filename']; ?>" />
                        </div>
                        <div>
                            <label>Image Alt Text: </label>
                            <input type="text" name="alt_text" id="alt_text" class="inputText" value="<?php echo $_POST['alt_text'] ?>" />
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Link Stuff</legend>
                        <div>
                            <label>URL: </label>
                            <input type="text" name="url" id="url" class="inputText" value="<?php echo $_POST['url'] ?>" />
                        </div>
                        <div>
                            <label>Title: </label>
                            <input type="text" name="title" id="title" class="inputText" value="<?php echo $_POST['title'] ?>" />
                        </div>
                    </fieldset>

                    <input type="submit" name="submit" value="Let's Do This!" />
                <?php if($_POST['badge_id']){ ?>
                    <input type="submit" name="delete" value="Delete This Nonsense!" />
                <?php } ?>
                </div>

                <?php if($badge_code){ ?>

                    <div id="badge_code">
                        <p>Copy this code</p>
                        <textarea rows='5' cols='50'><?php echo $badge_code; ?></textarea>
                    </div>

                    <div id="badge_preview">
                        <p>Here is a sweet preview!<p>
                        <?php echo $badge_code; ?>
                    </div>
        <?php
                }
        }

        protected function create_update_badge($b, $image){

            //upload image to /home/swigen/public_html/img.wpromote.com/seo-badges
            if($image['name']){
                $show_code = self::upload_badge($image);
                if(!$show_code){
                    return false;
                }
                $src = WWW_BADGE.$image['name'];
            } else {
                 $src = WWW_BADGE.$b['filename'];
            }
            //spit out the code
            
            $code =    "<a href=\"{$b['url']}\" title=\"{$b['title']}\" target=\"_blank\">\n";
            $code .=        "\t<img src=\"{$src}\" alt=\"{$b['alt_text']}\"\n";
            $code .=    "</a>";

            //insert badge into the database
            $data = array(
                "id" => $b['badge_id'],
                "url" => $b['url'],
                "title" => $b['title'],
                "alt_text" => $b['alt_text']
            );
            if($image['name']){
                //only update the image if one has been chosen
                $data['filename'] = $image['name'];
            }
            //db::dbg();
            $new_id = db::insert_update("seo_badges", array("id"), $data);
            if($new_id) $_POST['badge_id'] = $new_id;

            return $code;
        }

        protected function get_bage_code($b){
            $src = WWW_BADGE.$b['filename'];
            //spit out the code

            $code =    "<a href=\"{$b['url']}\" title=\"{$b['title']}\" target=\"_blank\">\n";
            $code .=        "\t<img src=\"{$src}\" alt=\"{$b['alt_text']}\"\n";
            $code .=    "</a>";

            return $code;
        }

        protected function upload_badge($image){


            $upload_errors = array(
                UPLOAD_ERR_OK           => "No errors.",
                UPLOAD_ERR_INI_SIZE     => "Larger than upload_max_filesize.",
                UPLOAD_ERR_FORM_SIZE    => "Larger than form MAX_FILE_SIZE.",
                UPLOAD_ERR_PARTIAL      => "Partial upload.",
                UPLOAD_ERR_NO_FILE      => "No file.",
                UPLOAD_ERR_NO_TMP_DIR   => "No temporary directory.",
                UPLOAD_ERR_CANT_WRITE   => "Can't write to disk.",
                UPLOAD_ERR_EXTENSION    => "File upload stopped by extension."
            );

            //check if an upload has been submitted
            if(isset($image['name'])){
                $errors = array();
                $success = true;

                if(!$image || empty($image) || !is_array($image)){
                    $errors[] = "No file was uploaded.";
                    $success = false;

                }elseif($image['error'] != 0){

                    $errors[] = $upload_errors[$image['error']];
                    $success = false;
                }

                if($success){

                    $filename = $image['name'];
                    $target_path = BADGE_PATH.$image['name'];
                    if(is_file($target_path)){
                        feedback::add_error_msg("The preview image already had this name.");
                        return true;
                    }
                    if(move_uploaded_file($image['tmp_name'], $target_path)) {
                        feedback::add_success_msg($filename.' has been uploaded successfully.');
                        return true;
                    } else {
                        feedback::add_error_msg("The file upload failed, possibly due to incorrect permissions on the upload folder.");
                    }
                } else {

                    echo join("<br />", $errors);
                    feedback::add_error_msg("Upload Error(s): ".$errors);

                }

            }

            return false;

        }

        protected function show_badge_gallery(){
            $badges = db::select("SELECT * FROM seo_badges", "ASSOC");
            foreach($badges as $b){
                $src = WWW_BADGE.$b['filename'];
                $out .= "<li badge_id='{$b['id']}'>";
                    $out .= "<a href='#' class='badge_details_link'>";
                        $out .= "<img src='{$src}' alt='{$b['alt_text']}' width='125px' height='125px'/>";
                    $out .= "</a>";
                $out .= "</li>";
            }
        ?>
                <h2>Badge Gallery</h2>
                <p>Click on a badge to view/edit</p>
                <input id="badge_id" name="badge_id" value="" type="hidden" />
                <ul id="badges">
                    <?php echo $out; ?>
                </ul>
        <?php
        }
}

?>