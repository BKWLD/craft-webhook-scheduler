/**
 * craft-entries-scheduler plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    Bukwild
 * @copyright Copyright (c) 2022 Bukwild
 * @link      https://bukwild.com
 * @package   Craftentriesscheduler
 * @since     1.0.0
 */

(function($) {
	/** global: Craft */
	/** global: Garnish */
	var Manager = Garnish.Base.extend(
		{
			init: function() {
				this.addListener($('.delete-webhook-btn'), 'activate', 'deleteWebhook');
			},


			deleteWebhook: function() {
				var id = $('.delete-webhook-btn').data( "id" )
				var confirmMsg = $('.delete-webhook-btn').data( "confirm" )

				if (confirm(confirmMsg)){
					Craft.postActionRequest('craft-entries-scheduler/default/delete', {id}, $.proxy(function(response, textStatus) {
						if (textStatus === 'success') {
							if (response.success) {
								Craft.cp.displayNotice(Craft.t('craft-entries-scheduler', 'Webhook deleted.'));
								location.href = Craft.getUrl('craft-entries-scheduler/');
							}
							else if (response.errors) {
								var errors = this.flattenErrors(response.errors);
								alert(Craft.t('craft-entries-scheduler', 'Could not delete the webhook:') + "\n\n" + errors.join("\n"));
							}
							else {
								Craft.cp.displayError();
							}
						}
					}, this));
				}
			},

			flattenErrors: function(responseErrors) {
				var errors = [];

				for (var attribute in responseErrors) {
					if (!responseErrors.hasOwnProperty(attribute)) {
						continue;
					}

					errors = errors.concat(responseErrors[attribute]);
				}

				return errors;
			}

		});


	Garnish.$doc.ready(function() {
		new Manager();
	});
})(jQuery);
