pipeline {
    agent any

    environment {
        REPO_URL = 'https://github.com/freelancernishad/api.rpclimo.com.git'  // Replace with your actual repository URL
        BRANCH = 'main'  // Replace with your branch name if different
        GIT_CREDENTIALS_ID = 'c2fdc78d-4805-475a-974b-c7445d7cb002'  // Your GitHub credentials ID
        DEPLOY_DIR = '/var/www/html'  // Set the path where you want to deploy the app
    }

    stages {
        stage('Checkout') {
            steps {
                // Checkout the code from the Git repository using the credentials
                git branch: "${BRANCH}", url: "${REPO_URL}", credentialsId: "${GIT_CREDENTIALS_ID}"
            }
        }

        stage('Install Dependencies') {
            steps {
                // Install Composer and dependencies for the Laravel project
                sh 'sudo apt update'
                sh 'sudo apt install -y php-cli php-mbstring unzip curl php-xml php-zip php-curl php-mysql'
                sh 'curl -sS https://getcomposer.org/installer | php'
                sh 'sudo mv composer.phar /usr/local/bin/composer'
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Setup Environment') {
            steps {
                // Copy the environment file and set permissions
                sh 'cp .env.example .env'
                sh 'php artisan key:generate'
                sh 'sudo chown -R www-data:www-data storage bootstrap/cache'  // Set the correct permissions for Laravel
            }
        }

        stage('Run Migrations') {
            steps {
                // Run database migrations
                sh 'php artisan migrate --force'  // Use --force to run migrations in production
            }
        }

        stage('Deploy') {
            steps {
                // Copy files to the deployment directory
                sh 'sudo cp -r * ${DEPLOY_DIR}/'

                // Set the correct ownership of the deployed files
                sh 'sudo chown -R www-data:www-data ${DEPLOY_DIR}/'

                // Clear Laravel cache and optimize the application
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan view:cache'
                sh 'php artisan optimize'
            }
        }
    }

    post {
        success {
            echo 'Deployment successful!'
        }

        failure {
            echo 'Deployment failed.'
        }
    }
}
