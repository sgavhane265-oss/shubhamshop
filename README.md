# ShubhamStore Cloud-Native E-Commerce Platform

**Developer:** Shubham Gavhane  

ShubhamStore is a fully cloud-native, highly available e-commerce platform built to demonstrate enterprise DevOps practices. The project showcases a journey from a modern PHP/Redis application to a fully orchestrated Kubernetes environment, utilizing Infrastructure as Code (Terraform) and Continuous Integration/Continuous Deployment (GitHub Actions).

---

## 🏗️ Architecture

- **Frontend Application:** Modern PHP 8.2 with Apache (Vanilla HTML/CSS/JS).
- **Caching Layer:** Redis (used for session and cart state management).
- **Containerization:** Docker & Docker Compose.
- **Orchestration:** Kubernetes & Helm.
- **Observability:** Prometheus & Grafana stack (embedded via Helm).
- **Cloud Infrastructure:** Terraform (AWS EKS, ECR, VPC).
- **CI/CD:** GitHub Actions (OIDC Authentication, Trivy Security Scanning, automated Helm Rollouts).

---

## 🚀 Getting Started

### Prerequisites
To run this project locally, you will need:
- [Docker & Docker Compose](https://www.docker.com/)
- [Minikube](https://minikube.sigs.k8s.io/) or another local Kubernetes cluster
- [Helm CLI](https://helm.sh/)
- [Terraform](https://www.terraform.io/) (If deploying to AWS)

### Option 1: Run Locally (Docker Compose)
For quick testing and development without Kubernetes, you can spin up the application using Docker Compose:

```bash
# Start the application and Redis
docker-compose up -d

# The app will be available at: http://localhost:8080
```

### Option 2: Run on Kubernetes (Minikube & Helm)
The primary deployment strategy uses Helm to deploy to Kubernetes. This enables autoscaling, self-healing, and metrics collection.

```bash
# 1. Start your local cluster
minikube start

# 2. Enable metrics for the Horizontal Pod Autoscaler (HPA)
minikube addons enable metrics-server

# 3. Deploy the application using Helm
helm upgrade --install shubhamstore-release ./helm/shubhamstore --namespace shubhamstore --create-namespace

# 4. Access the application!
minikube service shubhamstore -n shubhamstore
```

---

## 📈 Observability & Monitoring

The Helm chart is configured to automatically pull and install the `kube-prometheus-stack` as a dependency if enabled in `values.yaml`. 

This provides full cluster observability including:
- **Grafana Dashboards** (Track CPU, Memory, and Pod health)
- **Prometheus Metrics**
- **Alertmanager**

---

## ☁️ Cloud Deployment (AWS EKS)

The `terraform/` directory contains complete Infrastructure as Code to provision a highly-available AWS Elastic Kubernetes Service (EKS) cluster. 

**Cost-Conscious Design:** The architecture utilizes public subnets for worker nodes to bypass expensive NAT Gateway charges, making it perfect for cheap, temporary demonstrations.

```bash
cd terraform/
terraform init
terraform plan
terraform apply
```
*Note: Do not forget to run `terraform destroy` when you are finished to avoid AWS charges!*

---

## ⚙️ CI/CD Pipeline

This repository is equipped with a robust GitHub Actions workflow (`.github/workflows/ci.yml`).

Upon pushing to the `main` branch, the pipeline will automatically:
1. Validate PHP syntax.
2. Build the Docker Image using advanced layer caching.
3. Scan the image for vulnerabilities using **Trivy**.
4. Lint and validate the Kubernetes manifests (`kubeconform`) and Helm charts.
5. Authenticate with AWS securely via **OIDC (OpenID Connect)**.
6. Push the image to Amazon ECR.
7. Deploy the new release to AWS EKS via Helm with `--atomic` rollback protection.

---

## 🤝 Contributing
This project is open-source and intended as a learning reference for DevOps and Cloud-Native engineering. Contributions, issues, and feature requests are welcome!
