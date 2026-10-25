import app from 'flarum/admin/app';
import DiceSettingsPage from './DiceSettingsPage';
app.initializers.add('edgeinfinity/flarum-dice', () => {
  app.registry.for('edgeinfinity-dice').registerPage(DiceSettingsPage);
});
