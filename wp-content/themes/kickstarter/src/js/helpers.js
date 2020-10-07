// ==== Helpers Functions ==== //

// Usage $('#txt').html(sprintf('<span>Teamwork in </span> <strong>%s</strong>', msg));
var sprintf = function (str) {
	var args = arguments,
		flag = true,
		i = 1;

	str = str.replace(/%s/g, function () {
		var arg = args[i++];

		if (typeof arg === "undefined") {
			flag = false;
			return "";
		}
		return arg;
	});
	return flag ? str : "";
};
