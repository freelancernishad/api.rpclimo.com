pipeline {
    agent any

    environment {
        REPO_URL = 'https://github.com/freelancernishad/api.rpclimo.com.git'  // Replace with your actual repository URL
        BRANCH = 'main'  // Replace with your branch name if different
        GIT_CREDENTIALS_ID = 'c2fdc78d-4805-475a-974b-c7445d7cb002'  // Your GitHub credentials ID
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

        post {
            success {
                echo 'Deployment successful!'
            }

            failure {
                echo 'Deployment failed.'
            }
        }
    }
}
