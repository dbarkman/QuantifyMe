var quantifyMeURL = 'http://reallysimpleapps.com/quantifymeAPI/';
var quantifyMeAPIKey = '42m24fbJtEnAC6vXoVy8Yjz9U79dhZ3u';
var user = 'EBFE4C90-8103-4B67-8AB8-C8F654A71D88';

$(function() {
	$(".result").fadeOut(0);

	//get categories and fill out the dropdown
	getCategories('#categoriesDropDownList');
	getCategories('#createActionCategoriesDropDownList');

	$("#categoriesDropDownList").on('click', 'li a', function(){
		var uuid = $(this).attr("uuid");
		$("#categorySelectedField").val(uuid);
		$("#categoriesDropdownButton:first-child").text($(this).text()).val($(this).text()).html($(this).text() + ' <span class="caret"></span>');
		getActions(uuid);
	});

	$("#actionsDropDownList").on('click', 'li a', function(){
		var uuid = $(this).attr("uuid");
		$("#actionSelectedField").val(uuid);
		$("#actionsDropdownButton:first-child").text($(this).text()).val($(this).text()).html($(this).text() + ' <span class="caret"></span>');
	});

	$('#recordSubmitButton').on('click', function(e) {
		var category = $("#categorySelectedField").val();
		var action = $("#actionSelectedField").val();
		var detail = $("#detailsField").val();
		var description = $("#descriptionTextaera").val();
		var createRecordURI = quantifyMeURL + 'record?key=' + quantifyMeAPIKey;
		createRecordURI += '&category=' + category + '&action=' + action + '&detail=' + detail + '&description=' + description + '&user=' + user;
		requestJSON(createRecordURI, 'POST', function(json) {
			showResultAlert(json.result, "#recordSubmitResult");
		})
	});

	$("#createCategoryButton").on("click", function(e) {
		var name = $("#createCategoryField").val();
		var uri = quantifyMeURL + 'category?key=' + quantifyMeAPIKey + '&name=' + name;
		requestJSON(uri, 'POST', function(json) {
			getCategories('#categoriesDropDownList');
			getCategories('#createActionCategoriesDropDownList');
			showResultAlert(json.result, "#createCategoryResult");
		});
	});

	$("#createActionCategoriesDropDownList").on('click', 'li a', function(){
		var uuid = $(this).attr("uuid");
		$("#createActionCategorySelectedField").val(uuid);
		$("#createActionCategoriesDropdownButton:first-child").text($(this).text()).val($(this).text()).html($(this).text() + ' <span class="caret"></span>');
	});

	$("#createActionButton").on("click", function(e) {
		var category = $("#createActionCategorySelectedField").val();
		var name = $("#createActionField").val();
		var uri = quantifyMeURL + 'action?key=' + quantifyMeAPIKey + '&name=' + name + '&category=' + category;
		requestJSON(uri, 'POST', function(json) {
			//getActions('#actionsDropDownList');
			showResultAlert(json.result, "#createActionResult");
		});
	});

	//send a weight record to the api
	$('#weightSubmitButton').on('click', function(e) {
		var url = quantifyMeURL + 'record?key=' + quantifyMeAPIKey + '&category=030A4B6A-89F3-45D3-A872-FD8581161CBB&action=644123B6-5F82-4A6D-9C0F-5E7A900CFED9&user=EBFE4C90-8103-4B67-8AB8-C8F654A71D88&detail=';
		var weight = $('#weightField').val();
		var uri = url + weight;
		requestJSON(uri, 'POST', function(json) {
			showResultAlert(json.result, "#weightSubmitResult");
		});
	});

});

//general ajax function
function requestJSON(uri, type, callback) {
	console.log(uri);
	$.ajax({
		type: type,
		url: uri,
		complete: function(xhr) {
			callback.call(null, xhr.responseJSON);
		}
	});
}

//get categories
function getCategories(element) {
	var getCategoriesURI = quantifyMeURL + 'categories?key=' + quantifyMeAPIKey;
	requestJSON(getCategoriesURI, 'GET', function(json) {
		$(element).empty();
		var categoriesArray = json.data;
		$.each(categoriesArray, function(index) {
			$(element).append('<li><a uuid="' + categoriesArray[index].uuid + '" href="#">' + categoriesArray[index].name + '</a></li>');
		});
	});
}

//get actions
function getActions(uuid) {
	var getActionsURI = quantifyMeURL + 'actions?category=' + uuid + '&key=' + quantifyMeAPIKey;
	requestJSON(getActionsURI, 'GET', function(json) {
		$('#actionsDropDownList').empty();
		var actionsArray = json.data;
		$.each(actionsArray, function(index) {
			$('#actionsDropDownList').append('<li><a uuid="' + actionsArray[index].uuid + '" href="#">' + actionsArray[index].name + '</a></li>');
		});
	});
}

//show record result alert
function showResultAlert(result, element) {
	if (result == 'success') {
		$(element).removeClass("alert-danger").addClass("alert-success").text('Success!').fadeIn(1000).delay(1000).fadeOut(1500);
	} else {
		$(element).removeClass("alert-success").addClass("alert-danger").text('Fail!').fadeIn(1000).delay(1000).fadeOut(1500);
	}
}
