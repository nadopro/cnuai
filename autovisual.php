<?php
/*
 * autovisual.php
 * person.json을 읽어 D3.js 네트워크 다이어그램으로 시각화하는 독립 팝업 페이지
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>인물관계망 시각화</title>

    <!-- D3.js 최신 안정판 -->
    <script src="https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js"></script>

    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            font-family: "Malgun Gothic", "Apple SD Gothic Neo", Arial, sans-serif;
            background: #ffffff;
        }

        #toolbar {
            box-sizing: border-box;
            height: 86px;
            padding: 12px 18px;
            border-bottom: 1px solid #dddddd;
            background: #f8f9fa;
        }

        #toolbar h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            font-size: 14px;
            align-items: center;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .legend-line {
            display: inline-block;
            width: 34px;
            height: 0;
            border-top-width: 3px;
            border-top-style: solid;
        }

        .red {
            border-top-color: red;
        }

        .blue {
            border-top-color: blue;
            border-top-style: dashed;
        }

        .green {
            border-top-color: green;
        }

        .magenta {
            border-top-color: #FF00FF;
        }

        #network {
            width: 100%;
            height: calc(100% - 86px);
            display: block;
            cursor: grab;
            background: white;
        }

        #message {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            padding: 14px 18px;
            border: 1px solid #dddddd;
            border-radius: 8px;
            background: rgba(255,255,255,0.95);
            color: #555555;
            font-size: 15px;
            display: none;
            z-index: 10;
        }

        .node-label {
            font-size: 18px;
            font-weight: bold;
            fill: #222222;
            text-anchor: middle;
            dominant-baseline: middle;
            cursor: move;
            user-select: none;
            paint-order: stroke;
            stroke: white;
            stroke-width: 5px;
            stroke-linejoin: round;
        }

        .link-label {
            font-size: 12px;
            fill: #555555;
            text-anchor: middle;
            pointer-events: none;
            paint-order: stroke;
            stroke: white;
            stroke-width: 4px;
            stroke-linejoin: round;
        }
    </style>
</head>

<body>

<div id="toolbar">
    <h2>인물관계망 시각화</h2>

    <div class="legend">
        <span class="legend-item">
            <span class="legend-line red"></span>
            부자 / 모자
        </span>

        <span class="legend-item">
            <span class="legend-line blue"></span>
            친구
        </span>

        <span class="legend-item">
            <span class="legend-line green"></span>
            사제
        </span>

        <span class="legend-item">
            <span class="legend-line magenta"></span>
            형제 / 장인-사위 / 기타
        </span>
    </div>
</div>

<div id="message"></div>

<svg id="network"></svg>

<script>
(function () {

    const svg = d3.select("#network");
    const message = document.getElementById("message");

    let width = window.innerWidth;
    let height = window.innerHeight - 86;

    svg
        .attr("viewBox", `0 0 ${width} ${height}`)
        .attr("preserveAspectRatio", "xMidYMid meet");

    const zoomGroup = svg.append("g");
    const linkGroup = zoomGroup.append("g");
    const linkLabelGroup = zoomGroup.append("g");
    const nodeGroup = zoomGroup.append("g");

    function showMessage(text) {
        message.textContent = text;
        message.style.display = "block";
    }

    function hideMessage() {
        message.style.display = "none";
    }

    function getLinkColor(relation) {

        if (relation === "부자" || relation === "모자") {
            return "red";
        }

        if (relation === "친구") {
            return "blue";
        }

        if (relation === "사제") {
            return "green";
        }

        return "#FF00FF";
    }

    function getDashArray(relation) {
        return relation === "친구" ? "8,6" : null;
    }

    const zoom = d3.zoom()
        .scaleExtent([0.3, 5])
        .on("zoom", function(event) {
            zoomGroup.attr("transform", event.transform);
        });

    svg.call(zoom)
        .on("dblclick.zoom", null);

    svg.on("mousedown.cursor", function(event) {
        if (event.target === svg.node()) {
            d3.select(this).style("cursor", "grabbing");
        }
    });

    svg.on("mouseup.cursor mouseleave.cursor", function() {
        d3.select(this).style("cursor", "grab");
    });

    /*
     * person.json을 읽어옴.
     * 캐시 방지를 위해 현재 시간을 쿼리스트링에 추가.
     */
    d3.json("person.json?ts=" + Date.now())
        .then(function(graphData) {

            hideMessage();

            if (!graphData ||
                !Array.isArray(graphData.nodes) ||
                !Array.isArray(graphData.links)) {

                showMessage("person.json의 데이터 형식이 올바르지 않습니다.");
                return;
            }

            if (graphData.nodes.length === 0) {
                showMessage("등록된 인물이 없습니다.");
                return;
            }

            const link = linkGroup
                .selectAll("line")
                .data(graphData.links)
                .enter()
                .append("line")
                .attr("stroke", d => getLinkColor(d.relation))
                .attr("stroke-width", 2.5)
                .attr("stroke-dasharray", d => getDashArray(d.relation))
                .attr("stroke-opacity", 0.85);

            const linkLabel = linkLabelGroup
                .selectAll("text")
                .data(graphData.links)
                .enter()
                .append("text")
                .attr("class", "link-label")
                .text(d => d.relation);

            const node = nodeGroup
                .selectAll("text")
                .data(graphData.nodes)
                .enter()
                .append("text")
                .attr("class", "node-label")
                .text(d => d.id);

            const simulation = d3.forceSimulation(graphData.nodes)

                .force(
                    "link",
                    d3.forceLink(graphData.links)
                        .id(d => d.id)
                        .distance(160)
                        .strength(0.8)
                )

                .force(
                    "charge",
                    d3.forceManyBody()
                        .strength(-650)
                )

                .force(
                    "center",
                    d3.forceCenter(width / 2, height / 2)
                )

                .force(
                    "collision",
                    d3.forceCollide()
                        .radius(60)
                );

            node.call(
                d3.drag()

                    .on("start", function(event, d) {

                        event.sourceEvent.stopPropagation();

                        if (!event.active) {
                            simulation.alphaTarget(0.3).restart();
                        }

                        d.fx = d.x;
                        d.fy = d.y;
                    })

                    .on("drag", function(event, d) {
                        d.fx = event.x;
                        d.fy = event.y;
                    })

                    .on("end", function(event, d) {

                        if (!event.active) {
                            simulation.alphaTarget(0);
                        }

                        d.fx = null;
                        d.fy = null;
                    })
            );

            simulation.on("tick", function() {

                link
                    .attr("x1", d => d.source.x)
                    .attr("y1", d => d.source.y)
                    .attr("x2", d => d.target.x)
                    .attr("y2", d => d.target.y);

                node
                    .attr("x", d => d.x)
                    .attr("y", d => d.y);

                linkLabel
                    .attr("x", d => (d.source.x + d.target.x) / 2)
                    .attr("y", d => (d.source.y + d.target.y) / 2);
            });

            window.addEventListener("resize", function() {

                width = window.innerWidth;
                height = window.innerHeight - 86;

                svg.attr(
                    "viewBox",
                    `0 0 ${width} ${height}`
                );

                simulation.force(
                    "center",
                    d3.forceCenter(
                        width / 2,
                        height / 2
                    )
                );

                simulation.alpha(0.3).restart();
            });

        })

        .catch(function(error) {

            console.error(error);

            showMessage(
                "person.json 파일을 불러오지 못했습니다. " +
                "autovisual.php와 person.json이 같은 폴더에 있는지 확인하세요."
            );
        });

})();
</script>

</body>
</html>
