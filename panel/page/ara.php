<?php
$keyword = trim(guvenlik(urldecode($_GET["ara"])));
if (empty($keyword)) {
	yonlendir(".");
}
?>
<div id="search_page">
	<div id="search_page_top"><span>"<?php echo($keyword) ?>"</span> <?php echo(___("arama kriterinde (ARAMA_SONUC_SAYISI) sonuç bulundu.")); ?></div>
	<div id="search_results">
		<?php
		$modules_query_condition = "";
		if($UG->seviye > 1) {
			$modules_query_condition .= " and id in (select `modul_id` from `p_uye_grup_izinler` where `uye_grup_id`={$UG->ID} and `modul_id`>0 and `okuma`=1) ";
		}
		$modules_query = mysql_query("select * from `d_moduller` where `arama_yapilabilir`=1" . $modules_query_condition) or die(mysql_error());
		$found_any_record = false;
		$found_records = 0;
		while ($modules = mysql_fetch_array($modules_query)) {
			$fields = array();
			$fields_for_other_values = array();

			$name_field = fetch_one("d_modul_ozellikler", "id", $modules["name_field_prop_id"], "tablo_field");

			$module_fields_query = mysql_query(sprintf('select * from d_modul_ozellikler where `modul_id`=%1$d and ((`tip`=0 and `alt_tip`=0) or `tip` in (1, 5, 7))', $modules["id"]));
			// eger isimize yarar string bir field yoksa bir sonraki module geciyoruz
			if (mysql_num_rows($module_fields_query) == 0) {
				continue;
			}

			while ($module_fields = mysql_fetch_array($module_fields_query, MYSQL_ASSOC)) {
				$fields[] = $module_fields["tablo_field"];
				if (in_array($module_fields["tip"], array(0, 1, 7))) {
					if (!is_null($name_field) && $module_fields["tablo_field"] != $name_field) {
						$fields_for_other_values[] = $module_fields;
					}
				}
			}


			$query_condition = array();
			$query_condition[] = "deleted=0";

			$query_kw_condition = array();
			foreach ($fields as $field) {
				$query_kw_condition[] = "`{$field}` like '%{$keyword}%'";
			}
			$query_condition[] = "(" . implode(" or ", $query_kw_condition) . ")";
			unset($query_kw_condition);

			$ffov_string = "";
			if (count($fields_for_other_values) > 0) {
				for ($ffov = 0; $ffov < count($fields_for_other_values); $ffov++) {
					$ffov_string .= ", `{$fields_for_other_values[$ffov]["tablo_field"]}`";
				}
			}
			if (!is_null($name_field)) {
				$search_in_module_query_string = sprintf('select `UID`,`%3$s`%4$s from `%1$s` where %2$s order by `%3$s` asc', $modules["tablo_adi"], implode(" and ", $query_condition), $name_field, $ffov_string);
			} else {
				$search_in_module_query_string = sprintf('select `UID`%3$s from `%1$s` where %2$s order by `UID` asc', $modules["tablo_adi"], implode(" and ", $query_condition), $ffov_string);
			}
			$search_in_module_query = mysql_query($search_in_module_query_string) or die(mysql_error());
			$record_found = mysql_num_rows($search_in_module_query);

			if ($record_found > 0) {
				$found_any_record = true;
				?>
				<div class="expandable">
					<h1 class="fwn pr"><span class="dib pa sprite">&nbsp;</span><?php echo(___( $modules["ad"])) ?> (<?php echo($record_found); ?>)<span class="dib pa expander"><label><?php echo(___("Daralt")); ?></label><span class="dib pa">&nbsp;</span></span></h1>
					<div class="expand_layer">
						<div class="grpbx style3">
							<ul>
								<?php
								while ($records = mysql_fetch_array($search_in_module_query)) {
									$found_records++;
									?>
									<li>
										<a href="<?php echo(parse_panel_url(array("modulID" => $modules["id"], "islem" => "duzenle", "id" => $records[0]))); ?>" class="dib record_name"><?php echo(isset($records[1]) ? $records[1] : $records[0]); ?></a>
										<?php
										if (count($fields_for_other_values) > 0) {
											$ffov_br = false;
											$ffov_wr = 0;
											for ($ffov = 0; $ffov < count($fields_for_other_values); $ffov++) {
												$ffov_t = trim($records[$fields_for_other_values[$ffov]["tablo_field"]]);
												if (!empty($ffov_t)) {
													if ($ffov_br == false) {
														$ffov_br = true;
														echo("<br />");
													}
													echo($ffov_t);
													$ffov_wr++;
												}
											}
										}
										?>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
					</div>
				</div>
				<?php
			}
		}

		$t = ob_get_contents();
		ob_clean();
		$t = str_replace("ARAMA_SONUC_SAYISI", $found_records, $t);
		echo $t;
		if($found_records == 0) {
			dump_message("<em style='color: #0D94C4;'>`{$keyword}`</em> " . ___("için herhangi bir sonuç bulunamamıştır."), 4);
		}
		?>
	</div>
</div>