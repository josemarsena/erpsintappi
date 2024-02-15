<script>
	$(function(){
		'use strict';
		appValidateForm($('#add_edit_member'), {
			firstname: 'required',
			lastname: 'required',
			staff_identifi: 'required',
			status_work: 'required',
			job_position: 'required',
			password: {
				required: {
					depends: function(element) {
						return ($('input[name="isedit"]').length == 0) ? true : false
					}
				}
			},
			email: {
				required: true,
				email: true,
				remote: {
					url: site_url + "admin/misc/staff_email_exists",
					type: 'post',
					data: {
						email: function() {
							return $('input[name="email"]').val();
						},
						memberid: function() {
							return $('input[name="memberid"]').val();
						}
					}
				}
			},
			staff_identifi: {
				required: true,
				remote: {
					url: site_url + "admin/hr_profile/hr_code_exists",
					type: 'post',
					data: {
						staff_identifi: function() {
							return $('input[name="staff_identifi"]').val();
						},
						memberid: function() {
							return $('input[name="memberid"]').val();
						}
					}
				}
			}
		});

		init_datepicker();
		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');

		$('select[name="role"]').on('change', function() {
			var roleid = $(this).val();
			init_roles_permissions(roleid, true);
		});


		$("input[name='profile_image']").on('change', function() {
			readURL(this);
		});

	});

	function readURL(input) {
		"use strict";
		if (input.files && input.files[0]) {
			var reader = new FileReader();

			reader.onload = function (e) {
				$("img[id='wizardPicturePreview']").attr('src', e.target.result).fadeIn('slow');
			}
			reader.readAsDataURL(input.files[0]);
		}
	}

	function hr_profile_update_staff(staff_id) {
		"use strict";

		$("#modal_wrapper").load("<?php echo admin_url('hr_profile/hr_profile/member_modal'); ?>", {
			slug: 'update',
			staff_id: staff_id
		}, function() {
			if ($('.modal-backdrop.fade').hasClass('in')) {
				$('.modal-backdrop.fade').remove();
			}
			if ($('#appointmentModal').is(':hidden')) {
				$('#appointmentModal').modal({
					show: true
				});
			}
		});

		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');
	}

</script>