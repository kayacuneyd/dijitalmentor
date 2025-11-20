import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'de.bezmidar.app',
    appName: 'Bezmidar',
    webDir: 'build',

    server: {
        androidScheme: 'https', // HTTPS şart (security)
        // Development sırasında:
        // url: 'http://192.168.1.100:5173',
        // cleartext: true
    },

    plugins: {
        SplashScreen: {
            launchShowDuration: 2000,
            backgroundColor: '#2563eb' // Tailwind blue-600
        }
    }
};

export default config;
