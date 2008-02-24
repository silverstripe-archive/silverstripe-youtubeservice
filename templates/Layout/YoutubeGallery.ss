			<div class="youtubegallery typography">
				
			<% if YoutubeVideos.Count %>
				<ul class="youtubevideos">
				<% control YoutubeVideos %>
					<li>
						<div class="still">
							<% if Top.ShowVideoInPopup %>
								<a 
									params="lightwindow_width={$PlayerWidth},lightwindow_height={$PlayerHeight},lightwindow_loading_animation=false,lightwindow_type=external" 
									href="{$PlayerURL}" 
									class="lightwindow"
								>
									<% control SmallThumbnail %>
										<img src="$URL" alt="$Title" width="$Width" height="$Height" />
									<% end_control %>
								</a>
							<% else %>
								<a href="$PlayerURL" title="$title">
									<% control SmallThumbnail %>
										<img src="$URL" alt="$Title" width="$Width" height="$Height" />
									<% end_control %>
								</a>
							<% end_if %>
						</div>
						<div class="info">
							<h6>
								<% if Top.ShowVideoInPopup %>
									<a 
										params="lightwindow_width={$PlayerWidth},lightwindow_height={$PlayerHeight},lightwindow_loading_animation=false,lightwindow_type=external" 
										href="{$PlayerURL}" 
										class="lightwindow"
									>
										$Title
									</a>
								<% else %>
									<a href="$PlayerURL" title="$Title">$Title</a>
								<% end_if %>
							</h6>
							<p>
								$Description<br />
								<strong>Duration : </strong>$Runtime
							</p>
						</div>
						<div class="clearfix"></div>
					</li>
				<% end_control %>
				</ul>
				
				<div class="pages">
					<div class="paginator">
					</div>
					<span class="results">($YoutubeVideos.Count Videos)</span>
				</div>
			<% else %>
				<span>Sorry! Gallery doesn't contain any images for this page.</span>
			<% end_if %>
			
			</div>