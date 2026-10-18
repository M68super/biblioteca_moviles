/**
 * Motor de Renderizado Local con Medición Dinámica de Texto para Evitar Cortes
 */
class Chart {
    constructor(ctx, config) {
        this.ctx = ctx;
        this.config = config;
        this.init();
    }

    getColorForScore(score) {
        const factor = Math.min(Math.max((score - 1) / 9.0, 0), 1);
        const r = Math.round(255 * (1 - factor) + 46 * factor);
        const g = Math.round(50 * (1 - factor) + 204 * factor);
        const b = Math.round(50 * (1 - factor) + 113 * factor);
        return `rgb(${r}, ${g}, ${b})`;
    }

    init() {
        const canvas = this.ctx.canvas;
        const container = canvas.parentNode.getBoundingClientRect();
        
        canvas.width = container.width * window.devicePixelRatio;
        canvas.height = container.height * window.devicePixelRatio;
        canvas.style.width = container.width + "px";
        canvas.style.height = container.height + "px";

        const ctx = this.ctx;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

        const centerX = container.width / 2;
        const centerY = container.height / 2;

        // Configurar fuente para medir con precisión milimétrica
        ctx.font = "bold 9px 'Segoe UI', Arial, sans-serif";
        const labels = this.config.data.labels;
        let maxTextWidth = 0;
        for (let i = 0; i < labels.length; i++) {
            const w = ctx.measureText(labels[i]).width;
            if (w > maxTextWidth) maxTextWidth = w;
        }

        // CÁLCULO DINÁMICO: Ajustar el radio máximo en función del ancho del texto lateral
        const cos30 = 0.866;
        const maxRadiusFromWidth = (centerX - maxTextWidth - 10) / cos30;
        const maxRadiusFromHeight = centerY - 15;
        const maxRadius = Math.max(25, Math.min(centerX - 10, centerY - 10, maxRadiusFromWidth, maxRadiusFromHeight));

        // Extraer los datos desde la primera posición de la matriz datasets
        const dataset = this.config.data.datasets[0];
        const data = dataset.data;
        const totalSides = 6;

        ctx.clearRect(0, 0, container.width, container.height);

        // 1. Dibujar telaraña
        ctx.strokeStyle = "rgba(255, 255, 255, 0.08)";
        ctx.lineWidth = 1;
        for (let level = 2; level <= 10; level += 2) {
            ctx.beginPath();
            for (let i = 0; i < totalSides; i++) {
                const angle = (i * Math.PI / 3) - Math.PI / 2;
                const radius = maxRadius * (level / 10);
                ctx.lineTo(centerX + Math.cos(angle) * radius, centerY + Math.sin(angle) * radius);
            }
            ctx.closePath();
            ctx.stroke();
        }

        // 2. Dibujar ejes hacia los vértices
        ctx.beginPath();
        ctx.strokeStyle = "rgba(255, 255, 255, 0.12)";
        for (let i = 0; i < totalSides; i++) {
            const angle = (i * Math.PI / 3) - Math.PI / 2;
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(centerX + Math.cos(angle) * maxRadius, centerY + Math.sin(angle) * maxRadius);
        }
        ctx.stroke();

        // 3. Dibujar textos sin cortes
        for (let i = 0; i < totalSides; i++) {
            const angle = (i * Math.PI / 3) - Math.PI / 2;
            const x = centerX + Math.cos(angle) * (maxRadius + 8);
            const y = centerY + Math.sin(angle) * (maxRadius + 8);
            
            ctx.fillStyle = this.getColorForScore(data[i]);
            
            if (Math.abs(Math.cos(angle)) < 0.1) {
                ctx.textAlign = "center";
            } else {
                ctx.textAlign = Math.cos(angle) > 0 ? "left" : "right";
            }
            ctx.fillText(labels[i], x, y + 3);
        }

        // 4. Dibujar silueta rellena
        ctx.beginPath();
        ctx.fillStyle = dataset.backgroundColor;
        ctx.strokeStyle = dataset.borderColor;
        ctx.lineWidth = dataset.borderWidth;

        for (let i = 0; i < totalSides; i++) {
            const angle = (i * Math.PI / 3) - Math.PI / 2;
            const score = Math.max(1, data[i]);
            const radius = maxRadius * (score / 10);
            ctx.lineTo(centerX + Math.cos(angle) * radius, centerY + Math.sin(angle) * radius);
        }
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // 5. Dibujar cada línea exterior y vértice con su color semafórico dinámico individual
        for (let i = 0; i < totalSides; i++) {
            const angle = (i * Math.PI / 3) - Math.PI / 2;
            const nextAngle = (((i + 1) % totalSides) * Math.PI / 3) - Math.PI / 2;

            const score = Math.max(1, data[i]);
            const nextScore = Math.max(1, data[(i + 1) % totalSides]);

            const r1 = maxRadius * (score / 10);
            const r2 = maxRadius * (nextScore / 10);

            const x1 = centerX + Math.cos(angle) * r1;
            const y1 = centerY + Math.sin(angle) * r1;
            const x2 = centerX + Math.cos(nextAngle) * r2;
            const y2 = centerY + Math.sin(nextAngle) * r2;

            const gradient = ctx.createLinearGradient(x1, y1, x2, y2);
            gradient.addColorStop(0, this.getColorForScore(score));
            gradient.addColorStop(1, this.getColorForScore(nextScore));

            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.strokeStyle = gradient;
            ctx.lineWidth = 3;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(x1, y1, 4.5, 0, 2 * Math.PI);
            ctx.fillStyle = this.getColorForScore(score);
            ctx.fill();
            ctx.strokeStyle = "#ffffff";
            ctx.lineWidth = 1;
            ctx.stroke();
        }
    }
}