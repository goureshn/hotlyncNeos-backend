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
					
						<div id="client" class="settingmenu {{$selected['0']}}">
							<span>					
								Client
							</span>		
						</div>						
						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['0']}}"></span>	
						</div>						
						
						<div id="property" class="settingmenu {{$selected['1']}}">
							<span>						
								Property
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['1']}}"></span>	
						</div>						
						<div id="building" class="settingmenu {{$selected['2']}}">
							<span>						
								Building
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['2']}}"></span>	
						</div>						
						<div id="floor" class="settingmenu {{$selected['3']}}">
							<span>						
								Floor
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['3']}}"></span>	
						</div>						
						<div id="roomtype" class="settingmenu {{$selected['4']}}">
							<span>						
								Room Type
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['4']}}"></span>	
						</div>						
						<div id="room" class="settingmenu {{$selected['5']}}">
							<span>						
								Room
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