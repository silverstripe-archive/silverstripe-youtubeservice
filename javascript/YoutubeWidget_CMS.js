Behaviour.register({			
	'.YoutubeWidget select' : {
		initialise : function() {
			this.toggleFields();
		},
		onchange : function() {
				this.toggleFields();
		},
		
		toggleFields : function() {
				//hide all fields
				var widgetFields = $$('.YoutubeWidget .widgetFields div');
				for(i=1; i < 4; i++){
					Element.hide(widgetFields[i]);
				}
				
				switch(this.value){
					case '1':
						Element.toggle(widgetFields[2]);
						break;
					case '2':
						Element.toggle(widgetFields[3]);
						break;
					case '3':
						Element.toggle(widgetFields[1]);
						break;
					case '4':
						Element.toggle(widgetFields[1]);
						break;
				
				}
			}
		}
	});
	
	
	
			

