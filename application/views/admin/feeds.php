<?php 
/**
 * Feeds view page.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     API Controller
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
?>
			<div class="bg">
				<h2>
					<a href="<?php echo url::base() . 'admin/manage' ?>">Categories</a>
					<a href="<?php echo url::base() . 'admin/manage/forms' ?>">Forms</a>
					<a href="<?php echo url::base() . 'admin/manage/organizations' ?>">Organizations</a>
					<a href="<?php echo url::base() . 'admin/manage/pages' ?>">Pages</a>
					<a href="<?php echo url::base() . 'admin/manage/feeds' ?>" class="active">News Feeds</a>
					<span>(<a href="#add">Add New</a>)</span>
					<a href="<?php echo url::base() . 'admin/manage/reporters' ?>">Reporters</a>
				</h2>
				<!-- tabs -->
				<div class="tabs">
					<!-- tab -->
					<div class="tab">
						<ul>
							<li><a href="javascript:refreshFeeds();">REFRESH NEWS FEEDS</a></li><span id="feeds_loading"></span>
						</ul>
					</div>
				</div>
				<?php
				if ($form_error) {
				?>
					<!-- red-box -->
					<div class="red-box">
						<h3>Error!</h3>
						<ul>
						<?php
						foreach ($errors as $error_item => $error_description)
						{
							// print "<li>" . $error_description . "</li>";
							print (!$error_description) ? '' : "<li>" . $error_description . "</li>";
						}
						?>
						</ul>
					</div>
				<?php
				}

				if ($form_saved) {
				?>
					<!-- green-box -->
					<div class="green-box">
						<h3>The Feed Has Been <?php echo $form_action; ?>!</h3>
					</div>
				<?php
				}
				?>
				<!-- report-table -->
				<div class="report-form">
					<?php print form::open(NULL,array('id' => 'feedListing',
					 	'name' => 'feedListing')); ?>
						<input type="hidden" name="action" id="action" value="">
						<input type="hidden" name="feed_id" id="feed_id_action" value="">
						<div class="table-holder">
							<table class="table">
								<thead>
									<tr>
										<th class="col-1">&nbsp;</th>
										<th class="col-2">Feed</th>
										<th class="col-3">Items</th>
										<th class="col-4">Actions</th>
									</tr>
								</thead>
								<tfoot>
									<tr class="foot">
										<td colspan="4">
											<?php echo $pagination; ?>
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
									if ($total_items == 0)
									{
									?>
										<tr>
											<td colspan="4" class="col">
												<h3>No Results To Display!</h3>
											</td>
										</tr>
									<?php	
									}
									foreach ($feeds as $feed)
									{
										$feed_id = $feed->id;
										$feed_name = $feed->feed_name;
										$feed_url = $feed->feed_url;
										$feed_active = $feed->feed_active;
										$feed_count = ORM::factory('feed_item')->where('feed_id',$feed->id)->count_all();
										?>
										<tr>
											<td class="col-1">&nbsp;</td>
											<td class="col-2">
												<div class="post">
													<h4><?php echo $feed_name; ?></h4>
													<p><?php echo $feed_url; ?></p>
												</div>
											</td>
											<td><?php echo $feed_count; ?></td>
											<td class="col-4">
												<ul>
													<li class="none-separator"><a href="#add" onClick="fillFields('<?php echo(rawurlencode($feed_id)); ?>','<?php echo(rawurlencode($feed_name)); ?>','<?php echo(rawurlencode($feed_url)); ?>')">Edit</a></li>
													<li class="none-separator"><a href="javascript:feedAction('v','SHOW/HIDE','<?php echo(rawurlencode($feed_id)); ?>')"<?php if ($feed_active) echo " class=\"status_yes\"" ?>>Visible</a></li>
													<li><a href="javascript:feedAction('d','DELETE','<?php echo(rawurlencode($feed_id)); ?>')" class="del">Delete</a></li>
												</ul>
											</td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
						</div>
					<?php print form::close(); ?>
				</div>
				
				<!-- tabs -->
				<div class="tabs">
					<!-- tabset -->
					<a name="add"></a>
					<ul class="tabset">
						<li><a href="#" class="active">Add/Edit</a></li>
					</ul>
					<!-- tab -->
					<div class="tab">
						<?php print form::open(NULL,array('id' => 'feedMain',
						 	'name' => 'feedMain')); ?>
						<input type="hidden" id="feed_id" 
							name="feed_id" value="" />
						<input type="hidden" id="feed_active" 
							name="feed_active" vaule="" />
						<input type="hidden" name="action" 
							id="action" value=""/>
						<div class="tab_form_item">
							<strong>Feed Name:</strong><br />
							<?php print form::input('feed_name', '', 
							' class="text"'); ?>
						</div>
						<div class="tab_form_item">
							<strong>Feed URL:</strong><br />
							<?php print form::input('feed_url', '', ' class="text long"'); ?>
						</div>						
						<div class="tab_form_item">
							&nbsp;<br />
							<input type="image" src="<?php echo url::base() ?>media/img/admin/btn-save.gif" class="save-rep-btn" />
						</div>
						<?php print form::close(); ?>			
					</div>
				</div>
			</div>
