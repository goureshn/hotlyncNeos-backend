app.factory('DateService', function ($rootScope, $http) {
    Date.prototype.format = function(f) {
        if (!this.valueOf()) return " ";

        var weekName = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        var d = this;

        return f.replace(/(yyyy|yy|MM|dd|E|hh|mm|ss|a\/p)/gi, function($1) {
            switch ($1) {
                case "yyyy": return d.getFullYear();
                case "yy": return (d.getFullYear() % 1000).zf(2);
                case "MM": return (d.getMonth() + 1).zf(2);
                case "dd": return d.getDate().zf(2);
                case "E": return weekName[d.getDay()];
                case "HH": return d.getHours().zf(2);
                case "hh": return ((h = d.getHours() % 12) ? h : 12).zf(2);
                case "mm": return d.getMinutes().zf(2);
                case "ss": return d.getSeconds().zf(2);
                case "a/p": return d.getHours() < 12 ? "AM" : "PM";
                default: return $1;
            }
        });
    };

    String.prototype.string = function(len){var s = '', i = 0; while (i++ < len) { s += this; } return s;};
    String.prototype.zf = function(len){return "0".string(len - this.length) + this;};
    Number.prototype.zf = function(len){return this.toString().zf(len);};

    var DateService = {};
    DateService.getOnlyTime = function(datetime) {
        var date = Date.parse(datetime);

        var newdate = new Date(date);
        return newdate.format('hh:mm a/p');
    }

    DateService.getOnlyDate = function(datetime) {
        var date = Date.parse(datetime);

        var newdate = new Date(date);
        return newdate.format('dd-MM-yyyy');
    }

    return DateService;
});