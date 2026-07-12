import json
import requests
from tools.prices import get_price

OLLAMA_URL = "http://localhost:11434/api/chat"
MODEL = "llama3.2:3b"

SYSTEM_PROMPT = """Eres un agente de trading. Analizas precios de activos financieros.
Tienes tools disponibles y DEBES usarlas cuando necesites datos.
Cuando una tool te devuelve un resultado, ese dato es REAL y ACTUAL — fue ejecutado ahora mismo.
NUNCA digas que no puedes dar información en tiempo real si ya ejecutaste una tool y tienes el resultado.
Usa siempre el resultado de la tool en tu respuesta.
Si un activo movió más del 5%, menciona que es una variación significativa."""

TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "get_price",
            "description": "Obtiene el precio actual de un activo y su variación porcentual respecto al día anterior",
            "parameters": {
                "type": "object",
                "properties": {
                    "symbol": {
                        "type": "string",
                        "description": "Símbolo del activo: BTC, ETH, SPY, AAPL, etc.",
                    }
                },
                "required": ["symbol"],
            },
        },
    }
]

TOOL_MAP = {
    "get_price": get_price,
}


def run_agent(user_message: str):
    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": user_message},
    ]

    print(f"\nUsuario: {user_message}\n")1

    while True:
        response = requests.post(
            OLLAMA_URL,
            json={"model": MODEL, "messages": messages, "tools": TOOLS, "stream": False},
        )
        response.raise_for_status()
        msg = response.json()["message"]
        messages.append(msg)

        if msg.get("tool_calls"):
            for call in msg["tool_calls"]:
                fn_name = call["function"]["name"]
                fn_args = call["function"]["arguments"]
                if isinstance(fn_args, str):
                    fn_args = json.loads(fn_args)

                print(f"[tool] {fn_name}({fn_args})")
                result = TOOL_MAP[fn_name](**fn_args)
                print(f"[result] {result}\n")

                messages.append({
                    "role": "tool",
                    "content": json.dumps(result),
                })
        else:
            print(f"Agente: {msg['content']}")
            break


if __name__ == "__main__":
    run_agent("Dame un análisis matutino de BTC y ETH. ¿Alguno movió más del 5%?")
