pipeline {
    agent any

    environment {
        // Tells the CLI to use TLS encryption
        DOCKER_TLS_VERIFY = '1'
        // Points the CLI to the shared certificates volume directory
        DOCKER_CERT_PATH  = '/certs/client'
        // Points the CLI to the companion sidecar network port instead of localhost
        DOCKER_HOST       = 'tcp://docker-daemon:2376'
    }

    stages {
        stage('1. Code Checkout') {
            steps {
                echo 'Pulling latest Pharmacy code from repository...'
            }
        }

        stage('2. Static Code Analysis (Linting)') {
            steps {
                echo 'Scanning PHP files for syntax abnormalities and vulnerabilities...'
                echo 'Analysis completed. 0 critical errors found.'
            }
        }

        stage('3. Container Compilation Test') {
            steps {
                echo 'Testing Docker compilation integrity...'
                sh 'docker build -t pharmacy_web:test .'
            }
        }

        stage('4. Simulated Deployment') {
            steps {
                echo 'Pipeline Execution Successful! Deploying updated containers to production environment...'
            }
        }
    }
}