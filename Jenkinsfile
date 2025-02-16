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
                // Install Composer dependencies for the Laravel project
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Deploy') {
            steps {
                // Deploy to the server directory (e.g., /var/www/html)
                sh 'sudo cp -r * ${DEPLOY_DIR}/'

                // Set the correct ownership of the deployed files
                sh 'sudo chown -R www-data:www-data ${DEPLOY_DIR}/'
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
