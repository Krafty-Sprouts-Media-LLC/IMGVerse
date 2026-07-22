/**
 * Media modal entry — compiles shared App until Task 8 mounts MediaFrame.
 *
 * @package IMGVerse
 */

import '../scss/style.scss';
import App from './components/App';

// Retain the App graph in the production bundle for Tasks 8–9 mounts.
window.imgvApp = App;

export { App };
export default App;
