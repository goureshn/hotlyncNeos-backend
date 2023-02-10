@extends('layout.layout')
@section('content')
	<section style="float:left;width:1159px;height:100%">
		<link href="/css/admin_setting.css" rel="stylesheet">
		<div id="setting_div">			
			<div id="setting_sidebar" class="sidebar_back">
				@include('backoffice.wizard.sidebar')
			</div>
			<div id="setting_content">
				<div id="setting_sub_content">
				@section('setting_nav')
					<div class="setting_menu_group" style="margin-left:155px;">
						@if($errors->any())
							<h4>{{$errors->first()}}</h4>
						@endif
						<?php 
							$path = 'backoffice/property/wizard';
							$selected = array();
							for( $i = 0; $i < 6; $i++ )
							{
								if( $i < $step )
									array_push($selected, 'selector');
								else
									array_push($selected, '');
							}
								
						?>			
					
						<div id="user" class="settingmenu {{$selected['0']}}">
							<span>					
								User
							</span>		
						</div>						
						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['0']}}"></span>	
						</div>						
						
						<div id="permission_groups" class="settingmenu {{$selected['1']}}">
							<span>						
								Permission Groups
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['1']}}"></span>	
						</div>						
						<div id="usergroup" class="settingmenu {{$selected['2']}}">
							<span>						
								User Group
							</span>
						</div>						
					</div>		
					<div style="clear:both;margin-top:150px;positive:relative">
						@yield('setting_content')								
					</div>	
				@show	
				
				</div>
			
			</div>	
		</div>	
	</section>
	
@stop