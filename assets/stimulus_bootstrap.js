import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();

// Enregistrer le contrôleur Autocomplete de Symfony UX
import Autocomplete from '@symfony/ux-autocomplete/autocomplete_controller';
app.register('autocomplete', Autocomplete);
