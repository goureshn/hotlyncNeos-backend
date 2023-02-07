define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('MinibarCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.property_id = 0;
            $scope.minibar_posting_type_list = [
                'Item',
                'total',
            ];
            $scope.minibar_posting_checkout_allow = false;
            $scope.allow_minibar_post = false;
            $scope.disable_minibar_nopost = false;

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getMinibar('minibar');
            });

            $scope.getMinibar = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/minibar',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.minibar) {
                            $scope.displayMinibar(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.displayMinibar = function (data) {

                if(data.minibar.minibar_posting_type == 'Item') {
                    $scope.minibar_posting_type = $scope.minibar_posting_type_list[0];
                }

                if(data.minibar.minibar_posting_type == 'total') {
                    $scope.minibar_posting_type = $scope.minibar_posting_type_list[1];
                }


                if(data.minibar.minibar_posting_checkout_allow == 'true') {
                    $scope.minibar_posting_checkout_allow = true ;
                }

                if(data.minibar.minibar_posting_checkout_allow == 'false') {
                    $scope.minibar_posting_checkout_allow = false ;
                }

                if(data.minibar.allow_minibar_post == 1) {
                    $scope.allow_minibar_post = true;
                }
                if(data.minibar.allow_minibar_post == 0) {
                    $scope.allow_minibar_post = false;
                }

                $scope.disable_minibar_nopost = data.minibar.disable_minibar_nopost == 'Y' ? true : false;

                $scope.vat = data.minibar.vat;
                $scope.vat_no = data.minibar.vat_no;
                $scope.ser_chrg = data.minibar.ser_chrg;
                $scope.muncip_fee = data.minibar.muncip_fee;
            }

            $scope.saveMinibar = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
                if(fieldname == "minibar_posting_checkout_allow") {
                    if($scope.minibar_posting_checkout_allow == true){
                        data.fieldvalue = 'true';
                    }else {
                        data.fieldvalue = 'false';
                    }
                }

                if(fieldname == "allow_minibar_post") {
                    if($scope.allow_minibar_post == true){
                        data.fieldvalue = '1';
                    }else {
                        data.fieldvalue = '0';
                    }
                }

                if (fieldname == 'disable_minibar_nopost') {
                    data.fieldvalue = $scope.disable_minibar_nopost ? 'Y' : 'N';
                }

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveminibar',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'minibar' )
                                $scope.getMinibar('minibar');
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

        });
    });