pipeline {
    agent any

    environment {
        REPO_URL = 'https://github.com/freelancernishad/api.rpclimo.com.git'
        BRANCH = 'main' // Replace with your branch name if different
    }

    stages {
        stage('Checkout') {
            steps {
                // Checkout the code from the Git repository
                git branch: "${BRANCH}", url: "${REPO_URL}"
            }
        }

        stage('Install Dependencies') {
            steps {
                // Install dependencies for the project (Node.js example)
                sh 'sudo apt update'
                sh 'sudo apt install -y npm'
                sh 'npm install'
            }
        }

        stage('Build') {
            steps {
                // Build the project if applicable (e.g., npm build, etc.)
                sh 'npm run build'
            }
        }

        stage('Deploy') {
            steps {
                // Deploy the application to the server
                // You can use a script to copy files to the server or deploy via PM2 for Node.js
                sh 'sudo cp -r * /var/www/html/' // Example for Apache
                // If you're using PM2 for Node.js
                // sh 'pm2 start server.js'
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
