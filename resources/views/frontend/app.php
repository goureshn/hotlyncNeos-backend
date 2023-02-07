<!DOCTYPE html>
<!--[if !IE]><!--> 
<html lang="en"> 
    <head> 
        <meta charset="utf-8" /> 
        <title>EnnovaTech | Call Classification</title>
        
		<meta name="viewport" content="width=device-width, height=device-height, user-scalable=yes, initial-scale=1.0" id="dp_meta_viewport">
		
		<link href="/font-awesome-4.6.1/css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" href="/bootstrap/css/bootstrap.css">
		<link rel="stylesheet" href="/frontpanel/css/mystyle.css">
		<link rel="stylesheet" href="/frontpanel/font/font-awesome.css">
		
		
    </head>   
	<body> 
        <div id="dp_header">
			<div class="sourcepane-aligned">
				<div class="user-profile pull-left">
					<img class="gravatar pull-left" src="https://secure.gravatar.com/avatar/ec9907192c79d25313cf15f84eab1a2b?&amp;s=31&amp;d=mm">
					<div class="username">Hello, HotLync</div>
					<ul class="nav-profile">
						<li id="user_settings_link"><i class="icon-cog"></i> <a>Preferences</a></li>
						<li id="dp_header_help_trigger"><i class="icon-question-sign"></i> <a>Help</a></li>
						<li><i class="icon-signout"></i> <a>Log Out</a></li>
					</ul>
				</div>
			</div>
			
			<div id="dp_header_listpane_aligned" class="listpane-aligned">
				<div class="dp-omnibox-wrap">
					<div class="dp-omnibox" ng-class="{'is-active': isActive}" dp-omnibox id="dp_omnibox" ng-init="search_url = '/agent/ticket-search/custom-filter/run';">
						<div class="input-area">
							<i class="search-icon fa fa-search"></i>
							<div class="input-wrap">
								<input type="text" ng-show="mode == 'search'" ng-model="searchQuery" ng-change="touchSearch()" />
								<input type="text" ng-show="mode == 'recent'" placeholder="Type to filter recent results" id="recent_tabs_list_filter" />
							</div>
							<i class="x-icon fa fa-times-circle" ng-show="mode == 'search' && searchQuery.length" ng-click="clearSearch()" title="Clear search"></i>
							<div class="icons-well">
								<ul>
									<li class="recent" title="Recent Tabs" id="recent_tabs_btn" ng-click="toggleMode('recent')"><i class="fa fa-clock-o"></i></li>
									<li class="notif" title="Notifications" id="notifs_btn" ng-click="toggleMode('notif')"><i class="fa fa-bell"></i><em id="notifs_counts" data-count="0">0</em></li>
								</ul>
							</div>
						</div>
					</div>	
				
				</div>   
			</div>
			
			
			
			
			<div id="dp_header_contentpane_aligned" class="contentpane-aligned">		
				<div class="btn-group-chat pull-left" >
					<a class="btn" id="dp_header_userchat_btn">
						<div class="chat-big-icon pull-left"></div>
							<span class="status">
								<span class="dp-phrase-switch dp-phrase-on agent_chrome_chat_logged-in">Logged in to chat</span>
								<span class="dp-phrase-switch agent_chrome_chat_logged-out">Logged out of chat</span>
							</span>
						<ul class="info">
							<li><i class="icon-user"></i> <span class="dp-phrase agent_chrome_chat_online_agents" data-phrase-text="&lt;span class=&quot;userchat-online-agents-count&quot;&gt;{{count}}&lt;/span&gt; Agent|&lt;span class=&quot;userchat-online-agents-count&quot;&gt;{{count}}&lt;/span&gt; Agents" data-phrase-html="1"><span class="userchat-online-agents-count">0</span> Agents</span></li>
							<li><i class="icon-globe"></i> <span class="dp-phrase agent_chrome_chat_online_users" data-phrase-text="&lt;span class=&quot;userchat-online-users-count&quot;&gt;{{count}}&lt;/span&gt; User|&lt;span class=&quot;userchat-online-users-count&quot;&gt;{{count}}&lt;/span&gt; Users" data-phrase-html="1"><span class="userchat-online-users-count">0</span> Users</span></li>
						</ul>
						<div class="arrow">
							<span class="caret"></span>
						</div>
					</a>							
				</div>

				<div class="group pull-right" id="dp_header_logo_wrap">
					<a href="https://www.deskpro.com/" target="_blank" class="logo"></a>
				</div>

				<div class="panevis-switcher pull-right">
					<ul>
						<li ng-click="twoColumnsView()" ng-class="paneVis.list && paneVis.tabs && 'active'" class="view2" title="2 Column View"></li>						
						<li ng-click="oneColumnView()" ng-class="!(paneVis.list && paneVis.tabs) && 'active'" class="view3 active" title="1 Column View"></li>						
					</ul>
				</div>
			</div>
		</div>
		

		<!-- BEGIN NAVIGATION -->
		<div id="dp_nav">
			<div id="dp_nav_sections" class="deskproPane dp-appstrip">
				<ul class="is-nav-section is-default">
					<li class="active" id="tickets_section" data-section-handler="DeskPRO.Agent.WindowElement.Section.Tickets">
						<span class="count-badge" style="display: none"><span></span></span>
						<a title="Tickets"><i class="app-icon app-icon-tickets"></i></a>
					</li>
				
					<li  id="chat_section" data-section-handler="DeskPRO.Agent.WindowElement.Section.UserChat">
						<span class="count-badge" style="display: none"><span></span></span>
						<a title="User Chat"><i class="app-icon app-icon-chat"></i></a>
					</li>
				
					<li id="people_section" data-section-handler="DeskPRO.Agent.WindowElement.Section.People">
						<span class="count-badge" style="display: none"><span></span></span>
						<a title="CRM"><i class="app-icon app-icon-users"></i></a>
					</li>
				
					<li id="feedback_section" data-section-handler="DeskPRO.Agent.WindowElement.Section.Feedback">
						<span class="count-badge" style="display: none"><span></span></span>
						<a title="Feedback"><i class="app-icon app-icon-feedback"></i></a>
					</li>
				
					<li id="publish_section" data-section-handler="DeskPRO.Agent.WindowElement.Section.Publish">
						<span class="count-badge" style="display: none"><span></span></span>
						<a title="Publish"><i class="app-icon app-icon-publish"></i></a>
					</li>
				
					<li id="tasks_section" data-section-handler="DeskPRO.Agent.WindowElement.Section.Tasks" class="active">
						<span class="count-badge" style="display: none"><span></span></span>
						<a title="Tasks"><i class="app-icon app-icon-tasks"></i></a>
					</li>
					
				</ul>

				<ul class="btm">
					<li id="agents_section" class="no-click-switch section-on is-default is-nav-btn">
						<a title="You are currently using the Agent Interface" href="#"><i class="app-icon app-icon-agent-ifce"></i></a>
					</li>

					<li class="no-click-switch is-nav-btn">
						<a title="Go to Reports Interface" href="/reports/">
							<i class="app-icon app-icon-reports-ifce"></i>
						</a>
					</li>
				
								<li class="no-click-switch is-nav-btn">
						<a title="Go to Billing Interface" href="/admin/#/license">
							<i class="app-icon app-icon-billing-ifce"></i>
						</a>
					</li>

					<li id="settings_section" class="no-click-switch is-nav-btn">
						<a title="Go to Admin Interface" href="/admin/">
							<i class="app-icon app-icon-admin-ifce"></i>
						</a>
					</li>
					
					<li id="users_section" class="no-click-switch is-nav-btn">
						<a title="Go to User Interface" href="/">
							<i class="app-icon app-icon-user-ifce"></i>
						</a>
					</li>
				</ul>
			</div>
		</div>
		<!-- END NAVIGATION -->
		<div id="dp_center" style="left: 55px;">
			<div id="dp_source" style="left: 0px;">
				<div id="dp_source_btn" ng-click="toggleSourcePane()" ng-class="paneVis.source &amp;&amp; 'active'" class="active">
					<span class="collapse-btn"><i class="fa fa-chevron-left"></i></span>
					<span class="pin-btn" style="display: none;"><i class="fa fa-lock"></i></span>
				</div>
				
				<section id="task_outline" class="on">
					<div class="source-pane-wrapper">
						<div class="source-pane-instance ">
							<div class="source-pane-header">
								<ul class="pane-tabs">
									<li class="tab active"><i class="icon-tasks"></i> Tasks</li>
								</ul>
							</div>
							
							
							<div class="source-pane-content">
								<div class="layout-content with-scrollbar with-scroll-handler scroll-setup scroll-draw">
									<div class="scrollbar disable" style="height: 805px;">
										<div class="track" style="height: 805px;">
											<div class="thumb" style="top: 0px; height: 805px;">
												<div class="end"></div>
											</div>
										</div>
									</div>
									<div class="scroll-viewport scroll-disabled">
										<div class="scroll-content" style="top: 0px;">
											<div class="pane-content pane-content-main">
												<section class="pane-section last">
													<header>
														<h1>Tasks</h1>
													</header>
													
													<article>
														<ul class="nav-list">
															<li class="is-nav-item">
																<div class="item" data-route="listpane:/agent/tasks/list/own/total">
																	<em class="counter list-counter" id="tasks_counter_own_total">0</em>
																	<h3>My Tasks</h3>																	
																</div>
																<ul class="nav-list nav-list-small indented">
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/own/overdue">
																			<em class="counter list-counter count-in-badge" id="tasks_counter_own_overdue">0</em>
																			<h3>Overdue</h3>
																			
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/own/today">
																			<em class="counter list-counter count-in-badge" id="tasks_counter_own_today">0</em>
																			<h3>Due Today</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/own/future">
																			<em class="counter list-counter" id="tasks_counter_own_future">0</em>
																			<h3>Due In Future</h3>
																		</div>
																	</li>
																</ul>
															</li>

															<li class="is-nav-item">
																<div class="item" data-route="listpane:/agent/tasks/list/team/total">
																	<em class="counter list-counter" id="tasks_counter_team_total">0</em>
																	<h3>My Teams' Tasks</h3>
																</div>
																<ul class="nav-list nav-list-small indented">
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/team/overdue">
																			<em class="counter list-counter" id="tasks_counter_team_overdue">0</em>
																			<h3>Overdue</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/team/today">
																			<em class="counter list-counter" id="tasks_counter_team_today">0</em>
																			<h3>Due Today</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/team/future">
																			<em class="counter list-counter" id="tasks_counter_team_future">0</em>
																			<h3>Due In Future</h3>
																		</div>
																	</li>
																</ul>
															</li>
															
															<li class="is-nav-item">
																<div class="item" data-route="listpane:/agent/tasks/list/delegate/total">
																	<em class="counter list-counter" id="tasks_counter_delegated_total">0</em>
																	<h3>Tasks I Delegated</h3>
																</div>
																<ul class="nav-list nav-list-small indented">
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/delegate/overdue">
																			<em class="counter list-counter" id="tasks_counter_delegated_overdue">0</em>
																			<h3>Overdue</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/delegate/today">
																			<em class="counter list-counter" id="tasks_counter_delegated_today">0</em>
																			<h3>Due Today</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/delegate/future">
																			<em class="counter list-counter" id="tasks_counter_delegated_future">0</em>
																			<h3>Due In Future</h3>
																		</div>
																	</li>
																</ul>
															</li>

															<li class="is-nav-item">
																<div class="item" data-route="listpane:/agent/tasks/list/all/total">
																	<em class="counter list-counter" id="tasks_counter_all_total">0</em>
																	<h3>All Tasks</h3>
																</div>
																<ul class="nav-list nav-list-small indented">
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/all/overdue">
																			<em class="counter list-counter" id="tasks_counter_all_overdue">0</em>
																			<h3>Overdue</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/all/today">
																			<em class="counter list-counter" id="tasks_counter_all_today">0</em>
																			<h3>Due Today</h3>
																		</div>
																	</li>
																	<li class="is-nav-item">
																		<div class="item" data-route="listpane:/agent/tasks/list/all/future">
																			<em class="counter list-counter" id="tasks_counter_all_future">0</em>
																			<h3>Due In Future</h3>
																		</div>
																	</li>
																</ul>
															</li>
														</ul>
													</article>
													
													
												</section>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							
							
							
							
						</div>	
					</div>	
				</section>
			</div>
		</div>
		
        <div class="page-container">                
		    <div class="page-content-wrapper"> 
                <div ng-view class="page-content"> 
                    
                </div>
 			</div>
		</div>             
        
	<!--	
	<script data-main="frontpanel/js/main" src="/lib/require/require.js"></script>
	-->

    <!-- END BODY -->
</body>
</html>
